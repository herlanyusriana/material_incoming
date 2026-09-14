<?php

namespace Tests\Feature;

use App\Models\GciPart;
use App\Support\QrSvg;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    public function test_login_is_public_but_throttled_and_recovers_after_wait(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', ['username' => 'operator'])->assertUnprocessable();
        }
        $this->postJson('/api/auth/login', ['login' => ' OPERATOR '])
            ->assertStatus(429)->assertHeader('Retry-After');
        $this->postJson('/api/auth/login', ['username' => 'another'])->assertUnprocessable();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.15'])
            ->postJson('/api/auth/login', ['username' => 'operator'])->assertUnprocessable();
        $this->travel(61)->seconds();
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->postJson('/api/auth/login', ['username' => 'operator'])->assertUnprocessable();
    }

    public function test_ip_limit_blocks_rotating_account_names(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->postJson('/api/auth/login', ['username' => 'operator'.$i])->assertUnprocessable();
        }
        $this->postJson('/api/auth/login', ['username' => 'next'])->assertStatus(429);
    }

    public function test_sensitive_account_routes_have_throttle(): void
    {
        foreach (['register', 'forgot-password', 'reset-password', 'confirm-password', 'password'] as $uri) {
            $request = \Illuminate\Http\Request::create('/'.$uri, $uri === 'password' ? 'PUT' : 'POST');
            $route = app('router')->getRoutes()->match($request);
            $this->assertContains('throttle:account-security', $route->gatherMiddleware());
        }
    }

    public function test_line_stock_label_escapes_text_and_embeds_qr_as_image(): void
    {
        $attack = '<script>alert(1)</script>';
        app('translator')->addLines(['warehouse.labels.line_stock.scan_text' => "Scan\n".$attack], app()->getLocale());
        $qrSvg = QrSvg::make($attack);
        $this->assertNotEmpty($qrSvg);
        $html = view('warehouse.labels.line_stock_label', [
            'part' => new GciPart(['part_no' => $attack, 'part_name' => $attack]),
            'location' => $attack, 'qrSvg' => $qrSvg,
        ])->render();
        $this->assertStringNotContainsString($attack, $html);
        $this->assertStringContainsString(e($attack), $html);
        $this->assertStringContainsString('data:image/svg+xml;base64,'.base64_encode($qrSvg), $html);
        $this->assertStringNotContainsString('<svg', $html);
        $this->assertStringContainsString('white-space: pre-line', $html);
    }
}
