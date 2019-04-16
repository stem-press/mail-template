<?php

namespace Stem\MailTemplates\Metaboxes;

use Stem\Core\Context;
use Stem\Core\UI;
use Stem\MailTemplates\Models\MailTemplate;
use Stem\UI\MetaBox;

/**
 * Displays info about their membership card
 *
 * @package Stem\MailTemplates\Metaboxes
 */
class SendSampleEmail extends MetaBox {
	/** @inheritDoc */
	public function __construct(Context $context, UI $ui) {
		parent::__construct($context, $ui);

		if (is_admin() && current_user_can('edit_posts')) {
			add_action('wp_ajax_send_sample_email', [$this, 'sendSampleEmail']);
		}
	}

	/** @inheritDoc */
	public function id() {
		return 'mail-template-sender';
	}

	/** @inheritDoc */
	public function postTypes() {
		return [MailTemplate::postType()];
	}

	/** @inheritDoc */
	public function title() {
		return 'Send Sample Email';
	}

	/** @inheritDoc */
	public function screen() {
		return null;
	}

	/** @inheritDoc */
	public function context() {
		return self::CONTEXT_SIDE;
	}

	/** @inheritDoc */
	public function priority() {
		return self::PRIORITY_DEFAULT;
	}

	/** @inheritDoc */
	public function render($post) {
		return $this->ui->render('meta-boxes/mail-template/sample-sender', ['post' => $post]);
	}

	/**
	 * Sends a sample email
	 * @throws \Mailgun\Message\Exceptions\TooManyRecipients
	 * @throws \Samrap\Acf\Exceptions\BuilderException
	 */
	public static function sendSampleEmail() {
		if (!current_user_can('edit_posts')) {
			wp_send_json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
		}

		if (empty($_POST['nonce'])) {
			wp_send_json(['status' => 'error', 'message' => 'Missing nonce.'], 400);
		}

		if (!wp_verify_nonce($_POST['nonce'], 'send-sample-email')) {
			wp_send_json(['status' => 'error', 'message' => 'Invalid nonce.'], 403);
		}

		if (empty($_POST['email']) || empty($_POST['post'])) {
			wp_send_json(['status' => 'error', 'message' => 'Missing email or post field.'], 400);
		}

		/** @var MailTemplate $template */
		$template = MailTemplate::find($_POST['post']);
		if (empty($template)) {
			wp_send_json(['status' => 'error', 'message' => 'Missing template.'], 400);
		}

		$fieldsRegex = '/\{\{([aA-zZ0-9_-]+)\}\}/m';

		$fields = [];

		$text = $template->bodyText;
		preg_match_all($fieldsRegex, $text, $matches, PREG_SET_ORDER, 0);
		foreach($matches as $match) {
			if (!isset($fields[$match[1]])) {
				$fieldVal = ucwords(strtolower(str_replace('_', ' ', $match[1])));
				$fields[$match[1]] = $fieldVal;
			}
		}

		$text = $template->bodyHtml;
		preg_match_all($fieldsRegex, $text, $matches, PREG_SET_ORDER, 0);
		foreach($matches as $match) {
			if (!isset($fields[$match[1]])) {
				$fieldVal = ucwords(strtolower(str_replace('_', ' ', $match[1])));
				$fields[$match[1]] = $fieldVal;
			}
		}

		$template->send($_POST['email'], $fields);

		wp_send_json(['status' => 'ok']);
	}
}