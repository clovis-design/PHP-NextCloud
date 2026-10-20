<?php
  $value = $value ?? 0; 
  $label = $label ?? "Progress";
  $color = $color ?? "bg-blue-500";
  $clampedValue = max(0, min($value, 100)); // 0-100%
?>

<div class="flex items-center w-48 h-full">
  <div class="relative w-full h-2 bg-gray-200/10 rounded-full overflow-hidden">
    <div data-progress-bar style="width: <?= (float) $clampedValue ?>%" class="h-full rounded-full <?= htmlspecialchars($color) ?>"></div>
  </div>
</div>