<?php

$fields = get_fields() ?: [];
$color  = $fields['color'] ?? 'none';
// Garde-fou : un champ cloné peut revenir sous forme de tableau ou de valeur vide.
if (is_array($color)) {
	$color = $color['color'] ?? 'none';
}
$color  = in_array($color, ['none', 'primary', 'secondary', 'tertiary'], true) ? $color : 'none';

$size_class = function ($value, $custom, $prefix) {
	$classes = [
		'none'   => $prefix . '0',
		'small'  => rtrim($prefix, '-'),
		'medium' => $prefix . '50',
		'big'    => $prefix . '100',
	];

	if ($value === 'custom') {
		return $prefix . max(0, (int) $custom);
	}

	return $classes[$value] ?? $classes['none'];
};

$breakpoints     = ['mobile' => '', 'tablette' => 'md-', 'desktop' => 'lg-'];
$height_classes  = [];
$overlap_classes = [];

foreach ($breakpoints as $device => $breakpoint) {
	$properties        = is_array($fields["{$device}_property"] ?? null) ? $fields["{$device}_property"] : [];
	$height_classes[]  = $size_class($properties['height'] ?? 'none', $properties['custom_height'] ?? 0, 'ws-' . $breakpoint);
	$overlap_classes[] = $size_class($properties['overlap'] ?? 'none', $properties['custom_overlap'] ?? 0, 'ws-n-' . $breakpoint);
}

$classListHeight  = implode(' ', $height_classes) . ' ';
$classListOverlap = implode(' ', $overlap_classes) . ' ';

include __DIR__ . '/view.php';
