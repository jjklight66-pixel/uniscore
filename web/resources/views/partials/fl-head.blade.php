{{-- Floodlight design system: fonts, Tailwind tokens, shared CSS. Include inside <head>. --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700;800&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap&subset=vietnamese" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          night: '#0A1B30',
          stand: '#10253F',
          stand2: '#163152',
          seat: '#22406A',
          mist: '#8EA3BF',
          frost: '#E8EEF6',
          flood: '#F3F6FA',
          flood2: '#E6ECF3',
          chalk: '#D3DCE7',
          ink: '#0C1A2C',
          ink2: '#4A5D75',
          live: '#FFB020',
          liveink: '#8A5A00',
          goal: '#2FBF71',
          alert: '#E5484D',
        },
        fontFamily: {
          body: ['"Be Vietnam Pro"', 'system-ui', 'sans-serif'],
          score: ['"Barlow Condensed"', '"Be Vietnam Pro"', 'sans-serif'],
        },
      },
    },
  };
</script>
<link rel="stylesheet" href="{{ asset('css/floodlight.css') }}?v=1">
