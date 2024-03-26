<?php

namespace OutlawzTeam\Radicle\Support;

class JetformsFieldGenerator
{
	public static function generate(array $field)
	{
		// Initialize choices array
		$choices = [];

		// Fetch jetforms and generate choices
		$jetforms = get_posts([
			'post_type' => 'jet-form-builder',
			'posts_per_page' => -1,
		]);

		foreach ($jetforms as $jetform) {
			$form_id = $jetform->ID;
			$meta_json = get_post_meta($form_id, '_jf_args', true);

			$default_attributes = [
				'submit_type' => '',
				'required_mark' => '',
				'fields_layout' => '',
				'enable_progress' => null,
				'fields_label_tag' => '',
				'load_nonce' => '',
				'use_csrf' => '',
			];

			$meta = empty($meta_json) ? $default_attributes : json_decode($meta_json, true);

			$shortcode = '[jet_fb_form form_id="' . $form_id . '"';
			foreach ($meta as $key => $value) {
				if ($key === 'form_id') {
					continue;
				}
				$value = $value === null ? '' : $value;
				$shortcode .= sprintf(' %s="%s"', $key, esc_attr($value));
			}
			$shortcode .= ']';
			$choices[$shortcode] = $jetform->post_title;
		}

		// Set the field to select and assign the generated choices
		$field['type'] = 'select';
		$field['choices'] = $choices;

		return $field;
	}
}
