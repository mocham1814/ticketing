<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Myticket extends CI_Controller {

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


 function myticket_list()
 {

 	    $data['header'] = "header/header";
        $data['navbar'] = "navbar/navbar";
        $data['sidebar'] = "sidebar/sidebar";
        $data['body'] = "body/myticket";

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

        $id = trim($this->session->userdata('id_user'));

        $datamyticket = $this->model_app->datamyticket($id);
	    $data['datamyticket'] = $datamyticket;
        
        $this->load->view('template', $data);
 }


 function myticket_detail($id)
 {
    $data['header'] = "header/header";
        $data['navbar'] = "navbar/navbar";
        $data['sidebar'] = "sidebar/sidebar";
        $data['body'] = "body/progress_teknisi";

         $sql = "SELECT A.status, A.progress,A.tanggal, A.tanggal_solved, A.tanggal_proses, A.tanggal_solved, F.nama AS nama_teknisi, D.nama, C.id_kategori, A.id_ticket, A.tanggal, B.nama_sub_kategori, C.nama_kategori
                FROM ticket A 
                LEFT JOIN sub_kategori B ON B.id_sub_kategori = A.id_sub_kategori
                LEFT JOIN kategori C ON C.id_kategori = B.id_kategori 
                LEFT JOIN karyawan D ON D.nik = A.reported 
                LEFT JOIN teknisi E ON E.id_teknisi = A.id_teknisi
                LEFT JOIN karyawan F ON F.nik = E.nik 
                WHERE A.id_ticket = '$id'";

        $row = $this->db->query($sql)->row();

        $id_kategori = $row->id_kategori;

        $data['dd_teknisi'] = $this->model_app->dropdown_teknisi($id_kategori);
        $data['id_teknisi'] = "";
            
        $data['id_ticket'] = $id;  
        $data['nama_teknisi'] = $row->nama_teknisi;       
        $data['tanggal'] = $row->tanggal;
        $data['nama_sub_kategori'] = $row->nama_sub_kategori;
        $data['nama_kategori'] = $row->nama_kategori;
        $data['reported'] = $row->nama;
        $data['progress'] = $row->progress;
        $data['tanggal_proses'] = $row->tanggal_proses;

        $data['tanggal'] = $row->tanggal;
        $data['tanggal_solved'] = $row->tanggal_solved;

        //TRACKING TICKET
        $data_trackingticket = $this->model_app->data_trackingticket($id);
        $data['data_trackingticket'] = $data_trackingticket;

        
        $this->load->view('template', $data);
 }


 function feedback_yes($id_ticket, $id_teknisi)
 {

    

    $id_user = trim($this->session->userdata('id_user'));
    $tanggal = $time = date("Y-m-d  H:i:s");

    $data['feedback'] = 1;
    $data['reported'] = $id_user;
    $data['id_ticket'] = $id_ticket;


    $sql_teknisi = "SELECT * FROM teknisi WHERE id_teknisi = '$id_teknisi'";
    $row = $this->db->query($sql_teknisi)->row();

    $data2['point'] = $row->point + 1;
  
    $this->db->trans_start();

    $this->db->insert('history_feedback', $data);

    $this->db->where('id_teknisi', $id_teknisi);
    $this->db->update('teknisi', $data2);

    $this->db->trans_complete();

    if ($this->db->trans_status() === FALSE)
            {
               
                redirect('myticket/myticket_list');  

            } else  {

                $cek = "SELECT b.email FROM teknisi a
                LEFT JOIN karyawan b ON b.nik = a.nik
                WHERE a.id_teknisi = '$id_teknisi' LIMIT 1";

                $toEmail = $this->db->query($cek)->row();

                $this->send_email($toEmail->email, $id_ticket);
                
                redirect('myticket/myticket_list');   
            }



 }


 public function send_email($toEmail, $ticket) {

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
        $this->email->subject('Feedback positiv diberikan kepada Anda');

        // Isi email
        $this->email->message("Selamat, Feedback positiv untuk Ticket '.$ticket.' diberikan kepada Anda, Terima kasih atas kinerja Anda yang luar biasa");

        // Tampilkan pesan sukses atau error
        if ($this->email->send()) {
            echo 'Sukses! email berhasil dikirim.';
        } else {
            echo 'Error! email tidak dapat dikirim.';
        }
    }

 function feedback_no($id_ticket, $id_teknisi)
 {

    $id_user = trim($this->session->userdata('id_user'));
    $tanggal = $time = date("Y-m-d  H:i:s");

    $data['feedback'] = 0;
    $data['reported'] = $id_user;
    $data['id_ticket'] = $id_ticket;

    $sql_teknisi = "SELECT * FROM teknisi WHERE id_teknisi = '$id_teknisi'";
    $row = $this->db->query($sql_teknisi)->row();

    $data2['point'] = $row->point - 1;
  
    $this->db->trans_start();

    $this->db->insert('history_feedback', $data);

    $this->db->where('id_teknisi', $id_teknisi);
    $this->db->update('teknisi', $data2);

    $this->db->trans_complete();

    if ($this->db->trans_status() === FALSE)
            {
               
                redirect('myticket/myticket_list');   
            } else 
            {

                $cek = "SELECT b.email FROM teknisi a
                LEFT JOIN karyawan b ON b.nik = a.nik
                WHERE a.id_teknisi = '$id_teknisi' LIMIT 1";

                $toEmail = $this->db->query($cek)->row();

                $this->send_email2($toEmail->email, $id_ticket);
                
                redirect('myticket/myticket_list');   
            }
 }

 public function send_email2($toEmail, $ticket) {

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
      $this->email->subject('Feedback negativ diberikan kepada Anda');

      // Isi email
      $this->email->message("Ops, Feedback negativ untuk Ticket '.$ticket.' diberikan kepada Anda, silahkan koordinasikan dengan pembuat ticket");

      // Tampilkan pesan sukses atau error
      if ($this->email->send()) {
          echo 'Sukses! email berhasil dikirim.';
      } else {
          echo 'Error! email tidak dapat dikirim.';
      }
  }
    
public function pdfmyticket()
    {
    
    $id = trim($this->session->userdata('id_user'));

        $datamyticket = $this->model_app->datamyticket($id);
        $data['datamyticket'] = $datamyticket;


        ob_start();
        $this->load->view('body/pdfmyticket',$data);
        $html = ob_get_contents();
            ob_end_clean();
            
        require './assets/html2pdf/autoload.php';
    
        $pdf = new Spipu\Html2Pdf\Html2Pdf('L','A4','en');
        $pdf->WriteHTML($html);
        $pdf->Output('MyTicket.pdf', 'D');
    
    
    }

}
