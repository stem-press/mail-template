<?php

namespace Stem\MailTemplates\Jobs;

use Stem\MailTemplates\Models\MailTemplate;
use Stem\Queue\Job;

class SendMailTemplateJob extends Job {
	private $slug;
	private $email;
	private $data;
	private $inline;

	/**
	 * SendMailTemplateJob constructor.
	 *
	 * @param string $slug
	 * @param string|null $email
	 * @param array $data
	 * @param array $inline
	 */
	public function __construct($slug, $email = null, $data=[], $inline=[]) {
		$this->slug = $slug;
		$this->email = $email;
		$this->data = $data;
		$this->inline = $inline;
	}

	/**
	 * Runs the job, returning a status code
	 * @return int
	 */
	public function run() {
		try {
			MailTemplate::sendTemplate($this->slug, $this->email, $this->data, $this->inline);
		} catch (\Exception $ex) {
			return self::STATUS_ERROR;
		}

		return self::STATUS_OK;
	}
}