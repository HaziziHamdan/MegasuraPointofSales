<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Member;
use App\Models\Pembelian;
use App\Models\Pengeluaran;
use App\Models\Penjualan;
use App\Models\Produk;
use App\Models\Supplier;
use App\Models\Sessions;
use Illuminate\Http\Request;

use App\Models\PenjualanDetail;

class DashboardController extends Controller
{
    public function index()
    {
        $kategori = Kategori::count();
        $produk = Produk::count();
        $supplier = Supplier::count();
        $staff2 = Member::count();

        $staff = Sessions::leftJoin('users','users.id','sessions.user_id')
        ->select('users.name')
        ->where('sessions.user_id', '!=', 1)
        ->first('');

        $staff = $staff ? $staff->name: "None";
        
        $penjualan = format_uang(Penjualan::sum('bayar'));
        $pengeluaran = format_uang(Pengeluaran::sum('nominal'));
        $pembelian = format_uang(Pembelian::sum('bayar'));

        $tanggal_awal = date('Y-m-01');
        $tanggal_akhir = date('Y-m-d');

        $data_tanggal = array();
        $data_pendapatan = array();

        while (strtotime($tanggal_awal) <= strtotime($tanggal_akhir)) {
            //decrement
            $data_tanggal[] = (int) substr($tanggal_awal, 8, 2);

            //income calc
            $total_penjualan = Penjualan::where('created_at', 'LIKE', "%$tanggal_awal%")->sum('bayar');
            $total_pembelian = Pembelian::where('created_at', 'LIKE', "%$tanggal_awal%")->sum('bayar');
            $total_pengeluaran = Pengeluaran::where('created_at', 'LIKE', "%$tanggal_awal%")->sum('nominal');

            $pendapatan = $total_penjualan - $total_pembelian - $total_pengeluaran;
            $data_pendapatan[] += $pendapatan;
            
            //profit calc

            $tanggal_awal = date('Y-m-d', strtotime("+1 day", strtotime($tanggal_awal)));
        }

        while (strtotime($tanggal_awal) <= strtotime($tanggal_akhir)) {
            //decrement
            $data_tanggal[] = (int) substr($tanggal_awal, 8, 2);

            //income calc
            $total_penjualan = PenjualanDetail::where('created_at', 'LIKE', "%$tanggal_awal%", 'AND', 'id_produk', 'EQUAL', '"%$id_produk%"')->sum('bayar');

            $pendapatan = $total_penjualan;
            $data_pendapatan[] += $pendapatan;
            
            //profit calc

            $tanggal_awal = date('Y-m-d', strtotime("+1 day", strtotime($tanggal_awal)));
        }

        $tanggal_awal = date('Y-m-01');

        if (auth()->user()->level == 1) {
            return view('admin.dashboard', compact('kategori', 'produk', 'supplier', 'staff', 'penjualan', 'pengeluaran', 'pembelian', 'tanggal_awal', 'tanggal_akhir', 'data_tanggal', 'data_pendapatan'));
        } else {
            return view('kasir.dashboard');
        }
    }
}