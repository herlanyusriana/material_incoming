<?php

namespace App\Services;

use App\Models\NewSchema\Incoming\IncomingReceive as Receive;
use App\Models\NewSchema\Incoming\IncomingArrivalItem as ArrivalItem;
use App\Models\NewSchema\Incoming\IncomingArrival as Arrival;
use App\Models\NewSchema\Core\VendorPart;
use App\Models\NewSchema\Inventory\InventoryLocationStock;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Collection;

class ReceiveMaterialService
{
    public function resolveVendorPartId(ArrivalItem $arrivalItem): int
    {
        return (int) ($arrivalItem->vendor_part_id ?: $arrivalItem->part_id ?: 0);
    }

    public function resolveGciPartId(ArrivalItem $arrivalItem): ?int
    {
        if (!empty($arrivalItem->gci_part_id)) {
            return (int) $arrivalItem->gci_part_id;
        }
        if (!empty($arrivalItem->gciPart?->id)) {
            return (int) $arrivalItem->gciPart->id;
        }
        if (!empty($arrivalItem->vendorPart?->gci_part_id)) {
            return (int) $arrivalItem->vendorPart->gci_part_id;
        }

        $vendorPartId = $this->resolveVendorPartId($arrivalItem);
        if ($vendorPartId <= 0) {
            return null;
        }

        $gciPartId = VendorPart::query()->whereKey($vendorPartId)->value('gci_part_id');
        return !empty($gciPartId) ? (int) $gciPartId : null;
    }

    public function ensurePutawayGciPartId(ArrivalItem $arrivalItem, string $errorKey = 'tags'): int
    {
        $gciPartId = (int) ($this->resolveGciPartId($arrivalItem) ?? 0);
        if ($gciPartId > 0) {
            return $gciPartId;
        }

        $partNo = $arrivalItem->vendorPart?->vendor_part_no ?: ('ITEM-' . $arrivalItem->id);
        throw new HttpResponseException(
            back()->withInput()->withErrors([
                $errorKey => "Part {$partNo} belum terhubung ke GCI Part / Part Master, jadi putaway belum bisa diproses.",
            ])
        );
    }

    public function normalizeTag(?string $tag): ?string
    {
        $tag = is_string($tag) ? strtoupper(trim($tag)) : null;
        return ($tag === null || $tag === '') ? null : $tag;
    }

    public function resolveReceiveTag(?string $tag, ?int $receiveId = null, $receivedAt = null): ?string
    {
        $normalized = $this->normalizeTag($tag);
        if ($normalized !== null) {
            return $normalized;
        }
        if ($receiveId) {
            return Receive::generateSystemTag(
                $receiveId,
                $receivedAt ? Carbon::parse($receivedAt) : null
            );
        }
        return null;
    }

    public function hasPendingReceives(Arrival $arrival): bool
    {
        $arrival->loadMissing(['items.receives', 'containers.inspection']);
        $isLocal = strtolower((string) ($arrival->vendor?->vendor_type ?? '')) === 'local';

        if (!$isLocal && $arrival->containers && $arrival->containers->isNotEmpty()) {
            $hasMissingInspection = $arrival->containers->contains(fn($c) => !$c->inspection);
            if ($hasMissingInspection) {
                return true;
            }
        }

        if (!$isLocal) {
            $hasMissingTag = $arrival->items
                ->flatMap(fn($i) => $i->receives ?? collect())
                ->contains(fn($r) => !is_string($r->tag) || trim($r->tag) === '');
            if ($hasMissingTag) {
                return true;
            }
        }

        foreach ($arrival->items as $item) {
            $received = $item->receives->sum('qty');
            if (($item->qty_goods - $received) > 0) {
                return true;
            }
        }
        return false;
    }

    public function ensureTagsUniqueForArrivalItem(ArrivalItem $arrivalItem, array $tags, string $errorKey = 'tags', ?int $ignoreReceiveId = null): void
    {
        $incomingTags = collect($tags)
            ->pluck('tag')
            ->filter(fn($tag) => is_string($tag) && trim($tag) !== '')
            ->map(fn($tag) => strtoupper(trim($tag)))
            ->values();

        if ($incomingTags->isEmpty()) {
            return;
        }

        $duplicatesInRequest = $incomingTags
            ->countBy()
            ->filter(fn($count) => $count > 1)
            ->keys()
            ->values();

        if ($duplicatesInRequest->isNotEmpty()) {
            throw new HttpResponseException(back()->withInput()->withErrors([
                $errorKey => 'Ada TAG duplikat di input: ' . $duplicatesInRequest->implode(', '),
            ]));
        }

        $existingTags = $arrivalItem->receives()
            ->whereIn('tag', $incomingTags->all())
            ->when($ignoreReceiveId, fn($q) => $q->where('id', '!=', $ignoreReceiveId))
            ->pluck('tag')
            ->map(fn($tag) => strtoupper(trim((string) $tag)))
            ->unique()
            ->values();

        if ($existingTags->isNotEmpty()) {
            throw new HttpResponseException(back()->withInput()->withErrors([
                $errorKey => 'TAG sudah pernah diinput untuk item ini: ' . $existingTags->implode(', '),
            ]));
        }
    }

    public function ensureCompletedArrivalTransactionNo(Arrival $arrival): void
    {
        if (!empty($arrival->transaction_no) || $this->hasPendingReceives($arrival)) {
            return;
        }

        $receiveDate = Receive::query()
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->from('incoming_receives as receives')
            ->join('incoming_arrival_items as arrival_items', 'receives.arrival_item_id', '=', 'arrival_items.id')
            ->where('arrival_items.arrival_id', $arrival->id)
            ->whereNull('receives.deleted_at')
            ->selectRaw('MAX(COALESCE(receives.ata_date, receives.created_at)) as receive_at')
            ->value('receive_at');

        $transactionDate = $receiveDate
            ? Carbon::parse((string) $receiveDate)->toDateString()
            : ($arrival->invoice_date ? Carbon::parse((string) $arrival->invoice_date)->toDateString() : now()->toDateString());

        $arrival->transaction_no = Arrival::generateTransactionNo($transactionDate);
        $arrival->save();
    }
}
