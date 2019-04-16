<?php

return [
	'app' => [
		'models' => [
			\Stem\MailTemplates\Models\MailTemplate::class,
		]
	],
	'ui' => [
		'metaboxes' => [
			\Stem\MailTemplates\Metaboxes\SendSampleEmail::class
		]
	]
];