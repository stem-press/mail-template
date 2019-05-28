<?php

namespace Stem\MailTemplates\Logging;

use Stem\Core\Context;

class MailTemplateLog {
	private static $loggingEnabled = null;

	private static function loadConfig() {
		if (static::$loggingEnabled == null) {
			static::$loggingEnabled = Context::current()->setting('mail-templates/log', false);
		}
	}

	public static function LogSuccess($responseId, $response, $template, $to, $bcc, $data) {
		static::loadConfig();

		if (!static::$loggingEnabled) {
			return;
		}

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
		static::loadConfig();

		if (!static::$loggingEnabled) {
			return;
		}

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
		static::loadConfig();

		if (!static::$loggingEnabled) {
			return;
		}

		global $wpdb;
		$wpdb->query('delete from stem_mail_template_log');
	}
}