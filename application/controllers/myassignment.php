<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Myassignment extends CI_Controller {

function __construct(){
        parent::__construct();
        $this->load->model('model_app');

        if(!$this->session->userdata('id_user'))
       {
        $this->session->set_flashdata("msg", "<div class='alert alert-info'>
       <a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a>
       <strong><span class='glyphicon glyphicon-remove-sign'></span></strong> Silahkan login terlebih dahulu.
       </div>");
        redirect('login');
        }
    }


 function myassignment_list()
 {

 	    $data['header'] = "header/header";
        $data['navbar'] = "navbar/navbar";
        $data['sidebar'] = "sidebar/sidebar";
        $data['body'] = "body/myassignment";

        $id_dept = trim($this->session->userdata('id_dept'));
        $id_user = trim($this->session->userdata('id_user'));

        //notification 

        $sql_listticket = "SELECT COUNT(id_ticket) AS jml_list_ticket FROM ticket WHERE status = 3";
        $row_listticket = $this->db->query($sql_listticket)->row();

        $data['notif_list_ticket'] = $row_listticket->jml_list_ticket;

        $sql_approvalticket = "SELECT COUNT(A.id_ticket) AS jml_approval_ticket FROM ticket A 
        LEFT JOIN sub_kategori B ON B.id_sub_kategori = A.id_sub_kategori 
        LEFT JOIN kategori C ON C.id_kategori = B.id_kategori
        LEFT JOIN karyawan D ON D.nik = A.reported 
        LEFT JOIN departemen E ON E.id_dept = D.id_dept WHERE E.id_dept = $id_dept AND status = 1";
        $row_approvalticket = $this->db->query($sql_approvalticket)->row();

        $data['notif_approval'] = $row_approvalticket->jml_approval_ticket;

        $cari_teknisi = "SELECT id_teknisi FROM teknisi WHERE nik='$id_user'";
        $row_cari_teknisi = $this->db->query($cari_teknisi)->row();

        $id_teknisi = $row_cari_teknisi->id_teknisi;

        $sql_assignmentticket = "SELECT COUNT(id_ticket) AS jml_assignment_ticket FROM ticket WHERE status = 3 AND id_teknisi='$id_teknisi'";
        $row_assignmentticket = $this->db->query($sql_assignmentticket)->row();

        $data['notif_assignment'] = $row_assignmentticket->jml_assignment_ticket;

        //end notification
        
        
        $datamyassignment = $this->model_app->datamyassignment($id_user);
	    $data['datamyassignment'] = $datamyassignment;
        
        $this->load->view('template', $data);
 }


 function terima($ticket)
 {


    $id_user = trim($this->session->userdata('id_user'));
    $tanggal = $time = date("Y-m-d  H:i:s");

    $tracking['id_ticket'] = $ticket;
    $tracking['tanggal'] = $tanggal;
    $tracking['status'] = "Diproses oleh teknisi";
    $tracking['deskripsi'] = "";
    $tracking['id_user'] = $id_user;

    $data['status'] = 4;
    $data['tanggal_proses'] = $tanggal;
  
    $this->db->trans_start();

    $this->db->where('id_ticket', $ticket);
    $this->db->update('ticket', $data);

    $this->db->insert('tracking', $tracking);

    $this->db->trans_complete();

     if ($this->db->trans_status() === FALSE)
            {
               
                redirect('myassignment/myassignment_list'); 

            } else 
            {

                $cek_dept = "SELECT reported FROM ticket WHERE id_ticket = '$ticket'";

                $reported = $this->db->query($cek_dept)->row()->reported;

                $cek = "SELECT email FROM karyawan WHERE nik = '$reported' LIMIT 1";

                $toEmail = $this->db->query($cek)->row();

                $this->send_email($toEmail->email, $ticket);
                
                redirect('myassignment/myassignment_list');   
            }
 }


 public function send_email($toEmail, $ticket)
    {

      // Konfigurasi email
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

        // Load library email dan konfigurasinya
        $this->load->library('email', $config);

        // Email dan nama pengirim
        $this->email->from('mocham.anwar@gmail.com', 'Anwar');

        // Email penerima
        $this->email->to($toEmail); // Ganti dengan email tujuan

        // Lampiran email, isi dengan url/path file
        // $this->email->attach('https://masrud.com/content/images/20181215150137-codeigniter-smtp-gmail.png');

        // Subject email
        $this->email->subject('Ticket '.$ticket.' sudah diapproval oleh teknisi ');

        // Isi email
        $this->email->message("Ticket ".$ticket." sudah diapproval oleh teknisi, selanjutnya tinggal menunggu proses selesai, Terima kasih");

        // Tampilkan pesan sukses atau error
        if ($this->email->send()) {
            echo 'Sukses! email berhasil dikirim.';
        } else {
            echo 'Error! email tidak dapat dikirim.';
        }
    }

 function pending($ticket)
 {
    $data['status'] = 5;

    $id_user = trim($this->session->userdata('id_user'));
    $tanggal = $time = date("Y-m-d  H:i:s");

    $tracking['id_ticket'] = $ticket;
    $tracking['tanggal'] = $tanggal;
    $tracking['status'] = "Pending oleh teknisi";
    $tracking['deskripsi'] = "";
    $tracking['id_user'] = $id_user;
  
    $this->db->trans_start();

    $this->db->where('id_ticket', $ticket);
    $this->db->update('ticket', $data);

    $this->db->insert('tracking', $tracking);

    $this->db->trans_complete();

     if ($this->db->trans_status() === FALSE)
            {
               
                redirect('myassignment/myassignment_list');  
                
                $cek_dept = "SELECT reported FROM ticket WHERE id_ticket = '$ticket'";

                $reported = $this->db->query($cek_dept)->row()->reported;

                $cek = "SELECT email FROM karyawan WHERE nik = '$reported' LIMIT 1";

                $toEmail = $this->db->query($cek)->row();

                $this->send_email2($toEmail->email, $ticket);

            } else 
            {
                
                redirect('myassignment/myassignment_list');   
            }
 }


 public function send_email2($toEmail, $ticket)
    {

      // Konfigurasi email
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

        // Load library email dan konfigurasinya
        $this->load->library('email', $config);

        // Email dan nama pengirim
        $this->email->from('mocham.anwar@gmail.com', 'Anwar');

        // Email penerima
        $this->email->to($toEmail); // Ganti dengan email tujuan

        // Lampiran email, isi dengan url/path file
        // $this->email->attach('https://masrud.com/content/images/20181215150137-codeigniter-smtp-gmail.png');

        // Subject email
        $this->email->subject('Ticket '.$ticket.' dipending oleh teknisi: ');

        // Isi email
        $this->email->message("Ticket ".$ticket." dipending oleh teknisi :  selanjutnya tinggal menunggu proses approval, mohon untuk menunggu, Terima kasih");

        // Tampilkan pesan sukses atau error
        if ($this->email->send()) {
            echo 'Sukses! email berhasil dikirim.';
        } else {
            echo 'Error! email tidak dapat dikirim.';
        }
    }


 function ticket_detail($id)
 {

        $data['header'] = "header/header";
        $data['navbar'] = "navbar/navbar";
        $data['sidebar'] = "sidebar/sidebar";
        $data['body'] = "body/up_progress";

        $id_dept = trim($this->session->userdata('id_dept'));
        $id_user = trim($this->session->userdata('id_user'));

        //notification 

        $sql_listticket = "SELECT COUNT(id_ticket) AS jml_list_ticket FROM ticket WHERE status = 2";
        $row_listticket = $this->db->query($sql_listticket)->row();

        $data['notif_list_ticket'] = $row_listticket->jml_list_ticket;

        $sql_approvalticket = "SELECT COUNT(A.id_ticket) AS jml_approval_ticket FROM ticket A 
        LEFT JOIN sub_kategori B ON B.id_sub_kategori = A.id_sub_kategori 
        LEFT JOIN kategori C ON C.id_kategori = B.id_kategori
        LEFT JOIN karyawan D ON D.nik = A.reported 
        LEFT JOIN departemen E ON E.id_dept = D.id_dept WHERE E.id_dept = $id_dept AND status = 1";
        $row_approvalticket = $this->db->query($sql_approvalticket)->row();

        $data['notif_approval'] = $row_approvalticket->jml_approval_ticket;

        $sql_assignmentticket = "SELECT COUNT(id_ticket) AS jml_assignment_ticket FROM ticket WHERE status = 3 AND id_teknisi='$id_user'";
        $row_assignmentticket = $this->db->query($sql_assignmentticket)->row();

        $data['notif_assignment'] = $row_assignmentticket->jml_assignment_ticket;

        //end notification

        $sql = "SELECT A.progress, A.status, D.nama, C.id_kategori, A.id_ticket, A.tanggal, B.nama_sub_kategori, C.nama_kategori
                FROM ticket A 
                LEFT JOIN sub_kategori B ON B.id_sub_kategori = A.id_sub_kategori
                LEFT JOIN kategori C ON C.id_kategori = B.id_kategori 
                LEFT JOIN karyawan D ON D.nik = A.reported 
                WHERE A.id_ticket = '$id'";

        $row = $this->db->query($sql)->row();

        $id_kategori = $row->id_kategori;

        $data['url'] = "Myassignment/up_progress"; 

        $data['dd_teknisi'] = $this->model_app->dropdown_teknisi($id_kategori);
        $data['id_teknisi'] = "";
            
        $data['id_ticket'] = $id;  
        $data['progress'] = $row->progress;       
        $data['tanggal'] = $row->tanggal;
        $data['nama_sub_kategori'] = $row->nama_sub_kategori;
        $data['nama_kategori'] = $row->nama_kategori;
        $data['reported'] = $row->nama;
        
        $this->load->view('template', $data);

 }


 function up_progress()
 {

    
    $id_user = trim($this->session->userdata('id_user'));
    $tanggal = $time = date("Y-m-d  H:i:s");

    $ticket = strtoupper(trim($this->input->post('id_ticket')));

    $progress = strtoupper(trim($this->input->post('progress')));

    if($progress==100)
    {
        $data['status'] = 6;
        $data['tanggal_solved'] = $tanggal;

        $cek_dept = "SELECT reported FROM ticket WHERE id_ticket = '$ticket'";

        $reported = $this->db->query($cek_dept)->row()->reported;

        $cek = "SELECT email FROM karyawan WHERE nik = '$reported' LIMIT 1";

        $toEmail = $this->db->query($cek)->row();

        $this->send_email_up($toEmail->email, $ticket);
        
    }
    else
    {
        $data['status'] = 4;
    }

    $deskripsi_progress = strtoupper(trim($this->input->post('deskripsi_progress')));

    $tracking['id_ticket'] = $ticket;
    $tracking['tanggal'] = $tanggal;
    $tracking['status'] = "Up Progress To ".$progress." %";
    $tracking['deskripsi'] = $deskripsi_progress;
    $tracking['id_user'] = $id_user;

    $data['progress'] = $progress;

  
    $this->db->trans_start();

    $this->db->where('id_ticket', $ticket);
    $this->db->update('ticket', $data);

    $this->db->insert('tracking', $tracking);

    $this->db->trans_complete();

    if ($this->db->trans_status() === FALSE)
            {
               
                redirect('myassignment/myassignment_list');   
            } else 
            {
                
                redirect('myassignment/myassignment_list');  
            }


 }

  public function send_email_up($toEmail, $ticket){

      // Konfigurasi email
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

        // Load library email dan konfigurasinya
        $this->load->library('email', $config);

        // Email dan nama pengirim
        $this->email->from('mocham.anwar@gmail.com', 'Anwar');

        // Email penerima
        $this->email->to($toEmail); // Ganti dengan email tujuan

        // Lampiran email, isi dengan url/path file
        // $this->email->attach('https://masrud.com/content/images/20181215150137-codeigniter-smtp-gmail.png');

        // Subject email
        $this->email->subject('Ticket '.$ticket.' sudah selesai');

        // Isi email
        $this->email->message("Ticket ".$ticket." sudah selesai, silahkan berikan feedback untuk teknisi terkait, Terima kasih");

        // Tampilkan pesan sukses atau error
        if ($this->email->send()) {
            echo 'Sukses! email berhasil dikirim.';
        } else {
            echo 'Error! email tidak dapat dikirim.';
        }
    }
    
}
