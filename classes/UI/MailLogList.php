<?php

namespace Stem\MailTemplates\UI;

use Carbon\Carbon;
use Stem\UI\ListTable;

class MailLogList extends ListTable {

	public function __construct($args = []) {
		parent::__construct([
			'singlur' => 'Mail Log',
			'plural' => 'Mail Logs',
			'ajax' => false,
			'screen' => (!empty($args['screen'])) ? $args['screen'] : null
		]);
	}

	public function get_columns() {
		return [
			'date' => 'Date',
			'template' => 'Template',
			'to' => 'To',
			'error' => 'Error',
			'responseId' => 'Response ID',
			'response' => 'Response',
		];
	}

	public function prepare_items() {
		/** @var \WPDB $wpdb */
		global $wpdb;

		$offset = ($this->get_pagenum() - 1) * 100;
		$this->items = $wpdb->get_results("select * from stem_mail_template_log order by id desc limit 100 offset {$offset}");
		$this->_column_headers = [$this->get_columns(), [], []];
		$total =  $wpdb->get_var("select count(id) from stem_mail_template_log");
		$this->set_pagination_args([
			'total_items' => $total,
			'per_page' => 100,
			'total_pages' => ceil($total / 100)
		]);
	}

	protected function column_date($item) {
		$date = Carbon::parse($item->created_at);
		return $date->format('n/j/Y  g:i a');
	}

	protected function column_template($item) {
		return $item->template;
	}

	protected function column_to($item) {
		$toData = unserialize($item->email_to);

		return implode(', ', $toData);
	}

	protected function column_error($item) {
		return $item->error;
	}

	protected function column_responseId($item) {
		return $item->responseId;
	}

	protected function column_response($item) {
		return $item->response;
	}
}