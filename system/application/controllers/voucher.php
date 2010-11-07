<?php
class Voucher extends Controller {

	function Voucher()
	{
		parent::Controller();
		$this->load->model('Voucher_model');
	}

	function index()
	{
		redirect('voucher/show/all');
		return;
	}

	function show($voucher_type)
	{
		$page_data['page_links'] = array(
			'voucher/show/all' => 'All',
			'voucher/show/receipt' => 'Receipt',
			'voucher/show/payment' => 'Payment',
			'voucher/show/contra' => 'Contra',
			'voucher/show/journal' => 'Journal',
		);
		switch ($voucher_type)
		{
		case 'all' :
			$page_data['page_title'] = "All Vouchers";
			$data['voucher_type'] = "";
			$data['voucher_table'] = $this->_show_voucher();
			break;
		case 'receipt' :
			$page_data['page_title'] = "Receipt Vouchers";
			$data['voucher_type'] = "receipt";
			$data['voucher_table'] = $this->_show_voucher(1);
			break;
		case 'payment' :
			$page_data['page_title'] = "Payment Vouchers";
			$data['voucher_type'] = "payment";
			$data['voucher_table'] = $this->_show_voucher(2);
			break;
		case 'contra' :
			$page_data['page_title'] = "Contra Vouchers";
			$data['voucher_type'] = "contra";
			$data['voucher_table'] = $this->_show_voucher(3);
			break;
		case 'journal' :
			$page_data['page_title'] = "Journal Vouchers";
			$data['voucher_type'] = "journal";
			$data['voucher_table'] = $this->_show_voucher(4);
			break;
		default :
			$this->session->set_flashdata('error', "Invalid voucher type");
			redirect('voucher/show/all');
			return;
			break;
		}
		$this->load->view('template/header', $page_data);
		$this->load->view('voucher/index', $data);
		$this->load->view('template/footer');
		return;
	}

	function _show_voucher($voucher_type = NULL)
	{
		if ($voucher_type > 5)
		{
			$this->session->set_flashdata('error', "Invalid voucher type");
			redirect('voucher/show/all');
			return;
		} else if ($voucher_type > 0) {
			$voucher_q = $this->db->query('SELECT * FROM vouchers WHERE type = ? ORDER BY date DESC ', array($voucher_type));
		} else {
			$voucher_q = $this->db->query('SELECT * FROM vouchers ORDER BY date DESC');
		}

		$html = "<table border=0 cellpadding=5 class=\"generaltable\">";
		$html .= "<thead><tr><th>Number</th><th>Date</th><th>Ledger A/C</th><th>Type</th><th>Status</th><th>DR Amount</th><th>CR Amount</th><th colspan=3>Actions</th></tr></thead>";
		$html .= "<tbody>";

		$odd_even = "odd";
		foreach ($voucher_q->result() as $row)
		{
			$this->tree .= "<tr class=\"tr-" . $odd_even . "\">";
			$this->tree .= "<td>" . $row->number . "</td>";
			$this->tree .= "<td>" . $row->date . "</td>";
			$this->tree .= "<td>Ledger A/C</td>";
			$this->tree .= "<td>" . $row->type . "</td>";
			$this->tree .= "<td>" . $row->draft . "</td>";
			$this->tree .= "<td>" . $row->dr_total . "</td>";
			$this->tree .= "<td>" . $row->cr_total . "</td>";
			$this->tree .= "</tr>";
			$odd_even = ($odd_even == "odd") ? "even" : "odd";
		}
		$html .= "</tbody>";
		$html .= "</table>";
		return $html;
	}

	function add($voucher_type)
	{
		switch ($voucher_type)
		{
		case 'receipt' :
			$page_data['page_title'] = "New Receipt Voucher";
			$data['voucher_type'] = "receipt";
			break;
		case 'payment' :
			$page_data['page_title'] = "New Payment Voucher";
			$data['voucher_type'] = "payment";
			break;
		case 'contra' :
			$page_data['page_title'] = "New Contra Voucher";
			$data['voucher_type'] = "contra";
			break;
		case 'journal' :
			$page_data['page_title'] = "New Journal Voucher";
			$data['voucher_type'] = "journal";
			break;
		default :
			$this->session->set_flashdata('error', "Invalid voucher type");
			redirect('voucher/show/all');
			return;
			break;
		}

		/* Form fields */
		$data['voucher_number'] = array(
			'name' => 'voucher_number',
			'id' => 'voucher_number',
			'maxlength' => '11',
			'size' => '11',
			'value' => $this->Voucher_model->next_voucher_number(),
		);
		$data['voucher_date'] = array(
			'name' => 'voucher_date',
			'id' => 'voucher_date',
			'maxlength' => '11',
			'size' => '11',
			'value' => '01/11/2010',
		);
		$data['voucher_narration'] = array(
			'name' => 'voucher_narration',
			'id' => 'voucher_narration',
			'cols' => '50',
			'rows' => '4',
			'value' => '',
		);
		$data['voucher_type'] = $voucher_type;

		/* Form validations */
		$this->form_validation->set_rules('voucher_number', 'Voucher Number', 'trim|is_natural|unique[vouchers.number]');
		$this->form_validation->set_rules('voucher_date', 'Voucher Date', 'trim|required|is_date');
		$this->form_validation->set_rules('voucher_narration', 'trim');

		/* Debit and Credit amount validation */
		if ($this->input->post('ledger_dc', TRUE))
		{
			foreach ($this->input->post('ledger_dc', TRUE) as $id => $ledger_data)
			{
				$this->form_validation->set_rules('dr_amount[' . $id . ']', 'Debit Amount', 'trim|currency');
				$this->form_validation->set_rules('cr_amount[' . $id . ']', 'Credit Amount', 'trim|currency');
			}
		}

		/* Repopulating form */
		if ($_POST)
		{
			$data['voucher_number']['value'] = $this->input->post('voucher_number');
			$data['voucher_date']['value'] = $this->input->post('voucher_date');
			$data['voucher_narration']['value'] = $this->input->post('voucher_narration');
		}

		if ($this->form_validation->run() == FALSE)
		{
			$this->load->view('template/header', $page_data);
			$this->load->view('voucher/add', $data);
			$this->load->view('template/footer');
		}
		else
		{
			/* Checking for Debit and Credit Total */
			$data_all_ledger_id = $this->input->post('ledger_id', TRUE);
			$data_all_ledger_dc = $this->input->post('ledger_dc', TRUE);
			$data_all_dr_amount = $this->input->post('dr_amount', TRUE);
			$data_all_cr_amount = $this->input->post('cr_amount', TRUE);
			$dr_total = 0;
			$cr_total = 0;
			foreach ($data_all_ledger_dc as $id => $ledger_data)
			{
				if ($data_all_ledger_id[$id] < 1)
					continue;
				if ($data_all_ledger_dc[$id] == "D")
				{
					$dr_total += $data_all_dr_amount[$id];
				} else {
					$cr_total += $data_all_cr_amount[$id];
				}
			}
			if ($dr_total != $cr_total)
			{
				$this->session->set_flashdata('error', "Debit and Credit Total does not match!");
				$this->load->view('template/header', $page_data);
				$this->load->view('voucher/add', $data);
				$this->load->view('template/footer');
				return;
			}

			/* Adding main voucher */
			$data_number = $this->input->post('voucher_number', TRUE);
			$data_date = $this->input->post('voucher_date', TRUE);
			$data_narration = $this->input->post('voucher_narration', TRUE);
			$data_type = 0;
			switch ($voucher_type)
			{
				case "receipt": $data_type = 1; break;
				case "payment": $data_type = 2; break;
				case "contra": $data_type = 3; break;
				case "journal": $data_type = 4; break;
			}
			$data_date = date_php_to_mysql($data_date); // Converting date to MySQL
			$voucher_id = NULL;
			if ( ! $this->db->query("INSERT INTO vouchers (number, date, narration, draft, type) VALUES (?, ?, ?, 0, ?)", array($data_number, $data_date, $data_narration, $data_type)))
			{
				$this->session->set_flashdata('error', "Error addding Voucher A/C");
				$this->load->view('template/header', $page_data);
				$this->load->view('voucher/add', $data);
				$this->load->view('template/footer');
				return;
			} else {
				$voucher_id = $this->db->insert_id();
			}

			/* Adding ledger accounts */
			$data_all_ledger_dc = $this->input->post('ledger_dc', TRUE);
			$data_all_ledger_id = $this->input->post('ledger_id', TRUE);
			$data_all_dr_amount = $this->input->post('dr_amount', TRUE);
			$data_all_cr_amount = $this->input->post('cr_amount', TRUE);

			$dr_total = 0;
			$cr_total = 0;
			foreach ($data_all_ledger_dc as $id => $ledger_data)
			{
				$data_ledger_dc = $data_all_ledger_dc[$id];
				$data_ledger_id = $data_all_ledger_id[$id];
				if ($data_ledger_id < 1)
					continue;
				$data_amount = 0;
				if ($data_all_ledger_dc[$id] == "D")
				{
					$data_amount = $data_all_dr_amount[$id];
					$dr_total += $data_all_dr_amount[$id];
				} else {
					$data_amount = $data_all_cr_amount[$id];
					$cr_total += $data_all_cr_amount[$id];
				}

				if ( ! $this->db->query("INSERT INTO voucher_items (voucher_id, ledger_id, amount, dc) VALUES (?, ?, ?, ?)", array($voucher_id, $data_ledger_id, $data_amount, $data_ledger_dc)))
				{
					$this->session->set_flashdata('error', "Error addding Ledger A/C " . $data_ledger_id);
				}
			}

			/* Updating Debit and Credit Total in vouchers table */
			if ( ! $this->db->query("UPDATE vouchers SET dr_total = ?, cr_total = ? WHERE id = ?", array($dr_total, $cr_total, $voucher_id)))
			{
				$this->session->set_flashdata('error', "Error updating voucher total");
			}

			/* Success */
			$this->session->set_flashdata('message', "Voucher added successfully");
			redirect('voucher/show/' . $voucher_type);

			$this->load->view('template/header', $page_data);
			$this->load->view('voucher/add', $data);
			$this->load->view('template/footer');
		}
	}
}
