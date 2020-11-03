<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Approval extends CI_Controller {

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


 function approval_list()
 {

 	    $data['header'] = "header/header";
        $data['navbar'] = "navbar/navbar";
        $data['sidebar'] = "sidebar/sidebar";
        $data['body'] = "body/approval";

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

        $sql_assignmentticket = "SELECT COUNT(id_ticket) AS jml_assignment_ticket FROM ticket A LEFT JOIN
        karyawan B ON B.nik = A.reported
        LEFT JOIN departemen C ON C.id_dept = B.id_dept
         WHERE status = 3 AND id_teknisi='$id_user' AND C.id_dept ='$id_dept'";
        $row_assignmentticket = $this->db->query($sql_assignmentticket)->row();

        $data['notif_assignment'] = $row_assignmentticket->jml_assignment_ticket;

        //end notification
        
        $data['link'] = "approval_kabag/hapus";

       

        $dataapproval = $this->model_app->dataapproval($id_dept);
	    $data['dataapproval'] = $dataapproval;
        

        $this->load->view('template', $data);

 }

  function approval_no($ticket)
 {
 	
    $data['status'] = 0;

    $id_user = trim($this->session->userdata('id_user'));
    $tanggal = $time = date("Y-m-d  H:i:s");

    $tracking['id_ticket'] = $ticket;
    $tracking['tanggal'] = $tanggal;
    $tracking['status'] = "Ticket tidak disetujui";
    $tracking['deskripsi'] = "";
    $tracking['id_user'] = $id_user;

  
    $this->db->trans_start();

 	$this->db->where('id_ticket', $ticket);
 	$this->db->update('ticket', $data);

    $this->db->insert('tracking', $tracking);

 	$this->db->trans_complete();

    if ($this->db->trans_status() === FALSE)
            {
               
                redirect('approval/approval_list');   
            } else 
            {

                $cek_dept = "SELECT reported FROM ticket WHERE id_ticket = '$ticket'";

                $reported = $this->db->query($cek_dept)->row()->reported;

                $cek = "SELECT email FROM karyawan WHERE nik = '$reported' LIMIT 1";

                $toEmail = $this->db->query($cek)->row();

                $this->send_email2($toEmail->email, $ticket);
                
                redirect('approval/approval_list');   
            }

	
 }

 function approval_reaction($ticket)
 {

     $data['status'] = 1;

    $id_user = trim($this->session->userdata('id_user'));
    $tanggal = $time = date("Y-m-d  H:i:s");

    $tracking['id_ticket'] = $ticket;
    $tracking['tanggal'] = $tanggal;
    $tracking['status'] = "Ticket dikembalikan ke posisi belum di setujui";
    $tracking['deskripsi'] = "";
    $tracking['id_user'] = $id_user;

  
    $this->db->trans_start();

    $this->db->where('id_ticket', $ticket);
    $this->db->update('ticket', $data);

    $this->db->insert('tracking', $tracking);

    $this->db->trans_complete();

    if ($this->db->trans_status() === FALSE)
            {
               
                redirect('approval/approval_list');   
            } else 
            {
                
                redirect('approval/approval_list');   
            }

 }

  function approval_yes($ticket)
 {
   
    $data['status'] = 2;

    $id_user = trim($this->session->userdata('id_user'));
    $tanggal = $time = date("Y-m-d  H:i:s");

    $tracking['id_ticket'] = $ticket;
    $tracking['tanggal'] = $tanggal;
    $tracking['status'] = "Ticket disetujui";
    $tracking['deskripsi'] = "";
    $tracking['id_user'] = $id_user;
  
    $this->db->trans_start();

    $this->db->where('id_ticket', $ticket);
    $this->db->update('ticket', $data);

    $this->db->insert('tracking', $tracking);

    $this->db->trans_complete();

     if ($this->db->trans_status() === FALSE)
            {
                redirect('approval/approval_list');   
            } else 
            {

                $cek_dept = "SELECT reported FROM ticket WHERE id_ticket = '$ticket'";

                $reported = $this->db->query($cek_dept)->row()->reported;

                $cek = "SELECT email FROM karyawan WHERE nik = '$reported' LIMIT 1";

                $toEmail = $this->db->query($cek)->row();

                $this->send_email($toEmail->email, $ticket);
                
                redirect('approval/approval_list');   
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
            'smtp_user' => 'infopahoa@sekolah.pahoa.sch.id',  // Email gmail
            'smtp_pass'   => 'Pahoa12345',  // Password gmail
            'smtp_crypto' => 'ssl',
            'smtp_port'   => 465,
            'crlf'    => "\r\n",
            'newline' => "\r\n"
        ];

        // Load library email dan konfigurasinya
        $this->load->library('email', $config);

        // Email dan nama pengirim
        $this->email->from('infopahoa@sekolah.pahoa.sch.id', 'Anwar');

        // Email penerima
        $this->email->to($toEmail); // Ganti dengan email tujuan

        // Lampiran email, isi dengan url/path file
        // $this->email->attach('https://masrud.com/content/images/20181215150137-codeigniter-smtp-gmail.png');

        // Subject email
        $this->email->subject('Ticket '.$ticket.' sudah diapproval ');

        // Isi email
        $this->email->message("Ticket ".$ticket." sudah diapproval, selanjutnya akan diproses oleh teknisi terkait, Terima kasih");

        // Tampilkan pesan sukses atau error
        if ($this->email->send()) {
            echo 'Sukses! email berhasil dikirim.';
        } else {
            echo 'Error! email tidak dapat dikirim.';
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
        $this->email->subject('Ticket '.$ticket.' tidak diapproval');

        // Isi email
        $this->email->message("Ticket ".$ticket." tidak diapproval, untuk teknisnya silahkan hubungi atasan terkait, Terima kasih");

        // Tampilkan pesan sukses atau error
        if ($this->email->send()) {
            echo 'Sukses! email berhasil dikirim.';
        } else {
            echo 'Error! email tidak dapat dikirim.';
        }
    }

 function hapus()
 {
 	$id = $_POST['id'];

 	$this->db->trans_start();

 	$this->db->where('id_jabatan', $id);
 	$this->db->delete('jabatan');

 	$this->db->trans_complete();
	
 }

 function approval()
 {

 	    $data['header'] = "header/header";
        $data['navbar'] = "navbar/navbar";
        $data['sidebar'] = "sidebar/sidebar";
        $data['body'] = "body/form_jabatan";

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

	    $data['url'] = "jabatan/save";
			
		$data['id_jabatan'] = "";		
		$data['nama_jabatan'] = "";
        

        $this->load->view('template', $data);

 }

 



    
}
