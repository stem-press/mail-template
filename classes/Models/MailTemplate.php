<?php

namespace Stem\MailTemplates\Models;

use Mailgun\Mailgun;
use Mailgun\Message\MessageBuilder;
use Stem\Core\Context;
use Stem\Models\Attachment;
use Stem\Models\Post;
use Stem\Models\Utilities\CustomPostTypeBuilder;
use Stem\Models\Utilities\RepeaterPropertiesProxy;
use StoutLogic\AcfBuilder\FieldsBuilder;

/**
 * Represents a mail template
 *
 * @package TheWonder\Models
 *
 * @property string $subject
 * @property string $description
 * @property string $type
 * @property string $bodyText
 * @property string $bodyHtml
 * @property string $htmlTemplate
 * @property string $htmlHeader
 * @property RepeaterPropertiesProxy $toEmails
 * @property RepeaterPropertiesProxy $bccEmails
 * @property RepeaterPropertiesProxy $inlineAttachments
 */
class MailTemplate extends Post {

	/**
	 * MailTemplate constructor.
	 *
	 * @param Context|null $context
	 * @param \WP_Post|null $post
	 */
	public function __construct(Context $context = null, \WP_Post $post = null) {
		parent::__construct($context, $post);
	}

	#region Static Post Properties
	protected static $postType = 'mail-template';

	public static function initialize() {
		parent::initialize();

		if (is_admin() && current_user_can('edit_posts')) {
			add_action('wp_ajax_send_sample_email', [MailTemplate::class, 'sendSampleEmail']);
		}
	}

	/**
	 * Returns post type properties
	 * @return CustomPostTypeBuilder
	 */
	public static function postTypeProperties() {
		$builder = new CustomPostTypeBuilder(static::$postType, 'Mail Template', 'Mail Templates', static::$postType);
		return $builder
			->showInRest(false)
			->supportsTitle(true)
			->supportsEditor(false)
			->supportsThumbnail(false)
			->excludeFromSearch(true)
			->publicQueryable(false)
			->menuIcon('dashicons-email')
			->hasArchive(false)
			->metaboxCallback([static::class, 'registerMetaboxes'])
			;
	}

	/**
	 * Returns ACF fields definition
	 * @return array|null
	 * @throws \StoutLogic\AcfBuilder\FieldNameCollisionException
	 */
	public static function registerFields() {
		$userTemplates = apply_filters('heavymetal/mail/templates', []);

		$templates = ['none' => 'None'];

		foreach($userTemplates as $key => $template) {
			$templates[$key] = arrayPath($template, 'label', $key);
		}

		$builder = new FieldsBuilder(static::$postType);
		$builder->addText('subject')
			->setInstructions('The subject of the email.')
			->setRequired()
		->addText('description')
			->setInstructions('Description of the mail template.')
		->addSelect('type')
			->setInstructions("The type of email template.  For \"End User\", these are templates that are sent to the users of the site when they perform a certain action.  For \"internal\", these are emails sent to users or team members to notify them that something happened.")
			->addChoices(['user' => 'End User', 'internal' => 'Internal'])
			->setDefaultValue('user')
		->addRepeater('bcc_emails', ['label' => 'BCC', 'button_label' => 'Add Email', 'layout' => 'block'])
			->conditional('type', '==', 'user')
			->addEmail('email')
		->endRepeater()
		->addRepeater('to_emails', ['label' => 'To', 'button_label' => 'Add Email', 'layout' => 'block'])
			->conditional('type', '==', 'internal')
			->addEmail('email')
		->endRepeater()
		->addTab('Text Format')
			->addTextArea('body_text', ['label' => 'Body', 'rows' => 16, 'new_lines' => ''])
				->setInstructions('The body of the email in text format.')
		->addTab('HTML')
			->addSelect('html_template', ['label' => 'HTML Template', 'return_format' => 'value'])
				->setInstructions('The HTML template to use for the email.')
				->addChoices($templates)
				->setDefaultValue('none');

		foreach($userTemplates as $key => $template) {
			$vars = arrayPath($template, 'vars', []);
			foreach($vars as $var => $varData) {
				$label = arrayPath($varData, 'label', ucfirst($var));
				$instructions = arrayPath($varData, 'instructions', '');
				$builder
					->addText('html_template_'.$var, ['label' => $label])
					->setInstructions($instructions)
					->conditional('html_template', '==', $key);
			}
		}

		$builder
			->addWysiwyg('body_html', ['label' => 'Body', 'tabs' => 'all', 'tooldbar' => 'full', 'media_upload' => 1])
				->setInstructions('The body of the email in html format.')
			->addRepeater('inline_attachments', ['layout' => 'row', 'button_label' => 'Add Image'])
				->addImage('image', ['return_format' => 'id', 'preview_size' => 'thumbnail'])
		;


		return $builder->build();

	}

	/**
	 * Registers any related metaboxes
	 */
	public static function registerMetaboxes() {
		add_meta_box('mail-template-sender', 'Send Sample', [static::class, 'renderMailTemplateSender'], null, 'side', 'default');

	}
	//endregion

	//region Metaboxes
	/**
	 * Render the metabox for sending a sample email
	 * @param $post
	 */
	public static function renderMailTemplateSender($post) {
		echo Context::current()->ui->render('meta-boxes/mail-template/sample-sender', ['post' => $post]);
	}
	//endregion

	//region Template Rendering

	/**
	 * Renders the HTML template
	 *
	 * @throws \Samrap\Acf\Exceptions\BuilderException
	 */
	public function renderHtmlTemplate($text) {
		if (empty($this->htmlTemplate) || ($this->htmlTemplate == 'none')) {
			return $text;
		}

		$data = ['text' => $text];

		$userTemplates = apply_filters('heavymetal/mail/templates', []);
		if (!empty($userTemplates[$this->htmlTemplate])) {
			$template = $userTemplates[$this->htmlTemplate];
			$vars = arrayPath($template, 'vars', []);
			foreach($vars as $var => $varData) {
				$data[$var] = $this->getField('html_template_'.$var);
			}
		}

		return $this->context->ui->render($this->htmlTemplate, $data);
	}

	//endregion

	//region Mail Sending
	/**
	 * @param string|string[]|null $email
	 * @param array $data
	 * @param array $inline
	 *
	 * @return bool
	 * @throws \Mailgun\Message\Exceptions\TooManyRecipients
	 * @throws \Samrap\Acf\Exceptions\BuilderException
	 */
	public function send($email=null, $data=[], $inline=[]) {
		add_filter('max_srcset_image_width', function() { return 1; });

		if ($this->status != 'publish') {
			return false;
		}

		$smtpConfig = get_option('wp_mail_smtp');

		$mailgunKey = arrayPath($smtpConfig, 'mailgun/api_key', null);
		$domain = arrayPath($smtpConfig, 'mailgun/domain', null);
		$fromEmail = arrayPath($smtpConfig, 'mail/from_email', null);
		$fromName = arrayPath($smtpConfig, 'mail/from_name', null);

		if (empty($mailgunKey) || empty($domain) || empty($fromEmail) || empty($fromName)) {
			error_log('Missing config for mailgun.');
			return false;
		}

		$messageBldr = new MessageBuilder();
		$messageBldr->setFromAddress($fromEmail, ['full_name' => $fromName]);

		$toSet = false;
		if ($email != null) {
			$toSet=true;
			if (is_array($email)) {
				foreach($email as $emailAddress) {
					$messageBldr->addToRecipient($emailAddress);
				}
			} else {
				$messageBldr->addToRecipient($email);
			}
		}

		foreach($this->toEmails as $toEmail) {
			$messageBldr->addToRecipient($toEmail->email);
		}

		foreach($this->bccEmails as $bccEmail) {
			$messageBldr->addToRecipient($bccEmail->email);
		}

		$messageBldr->setSubject($this->subject);

		if (!empty($this->bodyText)) {
			$messageBldr->setTextBody($this->applyData($this->bodyText, $data));
		}

		if (!empty($this->bodyHtml)) {
			$html = $this->applyData($this->bodyHtml, $data);
			$html = $this->renderHtmlTemplate($html);

			/** @var Attachment $attachment */
			foreach($this->inlineAttachments as $attachment) {
				$filename = $attachment->image->filename;
				$localPath = $attachment->image->localFilePath;
				$url = $attachment->image->url;
				if (!file_exists($localPath)) {
					file_put_contents($localPath, file_get_contents($url));
				}

				$sizeUrls = $attachment->image->sizeUrls;
				foreach($sizeUrls as $size => $url) {
					$html = str_replace($url, "cid:$filename", $html);
				}

				$messageBldr->addInlineImage($localPath, $filename);
			}

			$messageBldr->setHtmlBody($html);
		}

		$messageBldr->setOpenTracking(true);
		$messageBldr->setClickTracking(false);

		foreach($inline as $key => $value) {
			$messageBldr->addInlineImage($value, $key);
		}

		if ($toSet) {
			$mg = Mailgun::create($mailgunKey);
			$mg->messages()->send($domain, $messageBldr->getMessage());

			return true;
		}

		return false;
	}

	/**
	 * Applies the data to the template
	 *
	 * @param $template
	 * @param array $data
	 *
	 * @return mixed|string|string[]|null
	 */
	private function applyData($template, $data=[]) {
		foreach($data as $key => $value) {
			$template = str_replace('{{'.$key.'}}',$value,$template);
		}

		$template = str_replace('{{site_url}}',home_url('/'), $template);

		$template = preg_replace("/(\{\{[aA-zZ0-9_-]+\}\})/", "", $template);

		return $template;
	}

	/**
	 * @param MailTemplate|string $slugOrTemplate
	 * @param string|string[]|null $email
	 * @param array $data
	 *
	 * @return bool
	 * @throws \Mailgun\Message\Exceptions\TooManyRecipients
	 * @throws \Samrap\Acf\Exceptions\BuilderException
	 */
	public static function sendTemplate($slugOrTemplate, $email = null, $data=[], $inline=[]) {
		if ($slugOrTemplate instanceof MailTemplate) {
			return $slugOrTemplate->send($email, $data);
		} else {
			$templatePost = get_page_by_path($slugOrTemplate,OBJECT,'mail-template');
			if (!empty($templatePost)) {
				/** @var MailTemplate $template */
				$template = Context::current()->modelForPost($templatePost);
				if(!empty($template)) {
					return $template->send($email, $data, $inline);
				}
			}
		}

		return false;
	}
	//endregion

	//region WordPress Admin

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
	//endregion
}