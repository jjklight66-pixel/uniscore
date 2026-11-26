@php
  $brandName = trim((string)($siteSettings['site_name'] ?? 'UNISCORE')) ?: 'UNISCORE';
  $brandLogo = trim((string)($siteSettings['site_logo_url'] ?? ''));
  $isUniScore = strcasecmp($brandName, 'UNISCORE') === 0;
@endphp
@if($brandLogo !== '')
  <img src="{{ $brandLogo }}" alt="{{ $brandName }}" class="h-7 w-auto object-contain rounded-[4px]" onerror="this.style.display='none'">
@endif
@if($isUniScore)
  <span><span class="text-frost">UNI</span><span class="text-live">SCORE</span></span>
@else
  <span class="text-frost">{{ $brandName }}</span>
@endif
