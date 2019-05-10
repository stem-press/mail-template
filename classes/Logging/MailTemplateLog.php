<?php

namespace Stem\MailTemplates\Logging;

class MailTemplateLog {
	public static function LogSuccess($responseId, $response, $template, $to, $bcc, $data) {
		/** @var \WPDB */
		global $wpdb;
		$wpdb->insert('stem_mail_template_log', [
			'template' => $template,
			'email_to' => serialize($to),
			'email_bcc' => serialize($bcc),
			'status' => (int)1,
			'data' => serialize($data),
			'responseId' => $responseId,
			'response' => $response
		]);
	}

	public static function LogError($template, $to, $bcc, $data, $error) {
		/** @var \WPDB */
		global $wpdb;
		$wpdb->insert('stem_mail_template_log', [
			'template' => $template,
			'email_to' => serialize($to),
			'email_bcc' => serialize($bcc),
			'status' => (int)0,
			'data' => serialize($data),
			'error' => $error
		]);
	}

	public static function Clear() {
		global $wpdb;
		$wpdb->query('delete from stem_mail_template_log');
	}
}