<?php

namespace Stem\MailTemplates\Jobs;

use Stem\MailTemplates\Models\MailTemplate;
use Stem\Queue\Job;
use Stem\Queue\Queue;

class SendMailTemplateJob extends Job {
	private $slug;
	private $email;
	private $data;
	private $inline;
	private $attachments;

	/**
	 * SendMailTemplateJob constructor.
	 *
	 * @param string $slug
	 * @param string|null $email
	 * @param array $data
	 * @param array $inline
	 * @param array $attachments
	 */
	public function __construct($slug, $email = null, $data=[], $inline=[], $attachments=[]) {
		$this->slug = $slug;
		$this->email = $email;
		$this->data = $data;
		$this->inline = $inline;
		$this->attachments = $attachments;
	}

	/**
	 * Runs the job, returning a status code
	 * @return int
	 */
	public function run() {
		try {
			MailTemplate::sendTemplate($this->slug, $this->email, $this->data, $this->inline, $this->attachments);
		} catch (\Exception $ex) {
			return self::STATUS_ERROR;
		}

		return self::STATUS_OK;
	}

	/**
	 * Adds a new SendMailTemplateJob to the queue
	 *
	 * @param $queue
	 * @param $slug
	 * @param null $email
	 * @param array $data
	 * @param array $inline
	 */
	public static function queue($queue, $slug, $email = null, $data=[], $inline=[], $attachments=[]) {
		$job = new SendMailTemplateJob($slug, $email, $data, $inline, $attachments);
		Queue::instance()->add($queue, $job);
	}
}