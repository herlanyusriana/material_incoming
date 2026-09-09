@props(['code', 'class' => 'h-4 w-6'])

@switch($code)
    @case('id')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 20 14" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <rect width="20" height="7" fill="#CE1126"/>
            <rect y="7" width="20" height="7" fill="#fff"/>
            <rect x=".5" y=".5" width="19" height="13" fill="none" stroke="rgba(15,23,42,.15)" rx="1.5"/>
        </svg>
        @break

    @case('en')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 60 30" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <rect width="60" height="30" fill="#012169"/>
            <path d="M0,0 L60,30 M60,0 L0,30" stroke="#fff" stroke-width="6"/>
            <path d="M0,0 L60,30 M60,0 L0,30" stroke="#C8102E" stroke-width="3.6"/>
            <path d="M30,0 V30 M0,15 H60" stroke="#fff" stroke-width="10"/>
            <path d="M30,0 V30 M0,15 H60" stroke="#C8102E" stroke-width="6"/>
            <rect x=".5" y=".5" width="59" height="29" fill="none" stroke="rgba(15,23,42,.15)"/>
        </svg>
        @break

    @case('ko')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 36 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <rect width="36" height="24" fill="#fff"/>
            <circle cx="18" cy="12" r="6" fill="#CD2E3A"/>
            <path d="M12,12 a6,6 0 0 0 12,0 a3,3 0 0 0 -6,0 a3,3 0 0 1 -6,0 z" fill="#0047A0"/>
            <rect x=".5" y=".5" width="35" height="23" fill="none" stroke="rgba(15,23,42,.15)" rx="2"/>
        </svg>
        @break
@endswitch
