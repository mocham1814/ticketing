<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{

		$config = [
            'mailtype'  => 'html',
            'charset'   => 'utf-8',
            'protocol'  => 'smtp',
            'smtp_host' => 'smtp.gmail.com',
            'smtp_user' => 'mocham.anwar@gmail.com',  // Email gmail
            'smtp_pass'   => '@1qaz2wsx',  // Password gmail
            'smtp_crypto' => 'ssl',
            'smtp_port'   => 465,
            'crlf'    => "\r\n",
            'newline' => "\r\n"
        ];


		 //Load library email dan konfigurasinya
		 $this->load->library('email', $config);

		 // Email dan nama pengirim
		 $this->email->from('mocham.anwar@gmail.com', 'Anwar');
 
		 // Email penerima
		 $this->email->to('clou.clouds@gmail.com'); // Ganti dengan email tujuan
 
		 // Lampiran email, isi dengan url/path file
		 $this->email->attach('https://masrud.com/content/images/20181215150137-codeigniter-smtp-gmail.png');
 
		 // Subject email
		 $this->email->subject('Kirim Email dengan SMTP Gmail CodeIgniter | MasRud.com');
 
		 // Isi email
		 $this->email->message("Ini adalah contoh email yang dikirim menggunakan SMTP Gmail pada CodeIgniter.<br><br> Klik <strong><a href='https://masrud.com/post/kirim-email-dengan-smtp-gmail' target='_blank' rel='noopener'>disini</a></strong> untuk melihat tutorialnya.");
 
		 // Tampilkan pesan sukses atau error
		 if ($this->email->send()) {
			 echo 'Sukses! email berhasil dikirim.';
		 } else {
			 echo 'Error! email tidak dapat dikirim.';
		 }

		$this->load->view('welcome_message');
	}
}
