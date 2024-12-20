<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\DataTables;

use App\Models\BaranggayRecordModel;
use App\Models\AgricultureModel;
use App\Models\PopulationTable;
use App\Models\FacilitiesModel;


class AdministratorController extends Controller
{
    //


    public function logout(){
        session()->flush();
        return redirect()->route('home');
    }


    public function add_user(Request $request){
        $request->validate([
            "user_name" => 'required|unique:user_table,user_name',
            "password" => 'required|confirmed'
        ]);

        $data = $request->all();
        unset($data['password_confirmation']);
        DB::table('user_table')->insert($data);
    }


    public function login(Request $request){
        $data = $request->all();
        $user_name = $data['user_name'];
        $password =  $data['password'];

        $user = DB::select("SELECT * FROM user_table WHERE user_name =  '$user_name'");

        if($user){
            $user_password =  $user[0]->password;
            if($user_password ==  $password){
                session(['user' => $user[0]]);

                return redirect()->route('administrator.home');
            }
        }

        throw ValidationException::withMessages(['unathorize_access'=> 'Invalid access']);
    }


    
    public function sign_in_page(){
        
        return view("sign_in");
    }


    public function home(){
        return view("administrator.home", [
            "images" => DB::select("SELECT * FROM image_table"),
            "person_incharge" => DB::select("SELECT * FROM person_incharge_table"),
        ]);
    }

    public function barangay(){
        return view("administrator.barangay", [
            "barangay_list" => DB::select("SELECT * FROM baranggay_table"),
            "business_list" => DB::select("SELECT * FROM business_table"),
            "biz_sec_List" => DB::select("SELECT * FROM biz_sec_table")
        ]);
    }


    public function agriculture(){
        return view("administrator.agriculture", [
            "barangay_list" => DB::select("SELECT * FROM baranggay_table"),
            "crop_type_list" => DB::select("SELECT * FROM agri_type_table")
        ]);
    }

    public function population(){
        return view("administrator.population", [
            "barangay_list" => DB::select("SELECT * FROM baranggay_table"),
            "business_list" => DB::select("SELECT * FROM business_table")
        ]);
    }

    public function facilities(){
        return view("administrator.facilities", [
            "barangay_list" => DB::select("SELECT * FROM baranggay_table"),
            "business_list" => DB::select("SELECT * FROM business_table")
        ]);
    }


    public function map(){
        return view("administrator.map");
    }

    public function contact(){
        return view("administrator.contact");
    }


    public function about(){
        return view("administrator.about");
    }



    public function upload_image(Request $request){
        $data =  $request->all();
        $image_number = $data['image_number'];

        $imageName = time().'.'.request()->image->getClientOriginalExtension();
        request()->image->move(public_path('images'), $imageName);

        DB::table('image_table')->where('id', $image_number)->update([
            "image_location" => "/images/$imageName"
        ]);
    }

    public function update_person_incharge(Request $request){
        $name = $request->name;
        $id =  $request->id;

        $data = [];
        if(request()->person_incharge_image){
            $imageName = time().'.'.request()->person_incharge_image->getClientOriginalExtension();
            request()->person_incharge_image->move(public_path('images'), $imageName);
            $data["image_location"] = "/images/$imageName";
        }

        $data["name"] = $name;

        DB::table('person_incharge_table')->where('id', $id)->update($data);

    }


    public function create_baranggay_record(Request $request){
        $data = $request->all();
        $id =  null;
        if(isset($data['id'])){
            $id =  $data['id'];
        }
        unset($data['id']);
        BaranggayRecordModel::updateOrCreate(["id" => $id], $data);
    }


    public function create_facility_record(Request $request){
        $data = $request->all();
        $id =  null;
        if(isset($data['id'])){
            $id =  $data['id'];
        }
        unset($data['id']);
        FacilitiesModel::updateOrCreate(["id" => $id], $data);
    }




    public function create_agri_record(Request $request){
        $data = $request->all();
        $id =  null;
        if(isset($data['id'])){
            $id =  $data['id'];
        }
        unset($data['id']);
        AgricultureModel::updateOrCreate(["id" => $id], $data);
    }


    public function create_population_record(Request $request){
        $data = $request->all();
        $id =  null;
        if(isset($data['id'])){
            $id =  $data['id'];
        }
        unset($data['id']);
        PopulationTable::updateOrCreate(["id" => $id], $data);
    }


    public function get_baranggay_record(){
        return DataTables::of(
            DB::select("SELECT a.*, b.name as business_type_name, c.name as baranggay_name, d.name as biz_sec_name 
                FROM baranggay_record_table a LEFT JOIN business_table b on b.id = a.business_type 
                LEFT JOIN baranggay_table c ON c.id = a.baranngay 
                LEFT JOIN biz_sec_table d on d.id =  a.biz_sec
                ORDER BY a.id DESC")
        )->make(true);
    }


    public function get_facility_record(){
        return DataTables::of(
            DB::select("SELECT a.*, c.name as baranggay_name  
                        FROM facilities_table a
                        LEFT JOIN baranggay_table c ON c.id = a.baranngay 
                        ORDER BY a.id DESC")
        )->make(true);
    }



    public function get_agri_record(){
        return DataTables::of(
            DB::select("SELECT a.*, b.name as agri_type_name, c.name as baranggay_name  
                        FROM agriculture_table a 
                        LEFT JOIN agri_type_table b on b.id = a.agri_type 
                        LEFT JOIN baranggay_table c ON c.id = a.baranngay 
                        ORDER BY a.id DESC")
        )->make(true);
    }



    public function get_population_record(){
        return DataTables::of(
            DB::select("SELECT a.*, c.name as baranggay_name  
                        FROM population_table a 
                        LEFT JOIN baranggay_table c ON c.id = a.baranngay 
                        ORDER BY a.id DESC")
        )->make(true);
    }


    public function get_baranggay_record_for_map(Request $request){
        $id =  $request->id;
        $biz_sec = $request->biz_sec;


        if($id && $biz_sec){
            $data = DB::select("SELECT a.*,b.color, b.name as business_type_name, c.name as baranggay_name, d.name 
                                FROM baranggay_record_table a 
                                LEFT JOIN business_table b on b.id = a.business_type 
                                LEFT JOIN baranggay_table c ON c.id = a.baranngay 
                                LEFT JOIN biz_sec_table d ON d.id = a.biz_sec
                                WHERE a.baranngay = $id AND d.id = $biz_sec 
                                ORDER BY a.id DESC");
        }else if($id){
            $data = DB::select("SELECT a.*,b.color, b.name as business_type_name, c.name as baranggay_name, d.name 
                                FROM baranggay_record_table a 
                                LEFT JOIN business_table b on b.id = a.business_type 
                                LEFT JOIN baranggay_table c ON c.id = a.baranngay 
                                LEFT JOIN biz_sec_table d ON d.id = a.biz_sec
                                WHERE a.baranngay = $id
                                ORDER BY a.id DESC");

        }else if($biz_sec){
            $data = DB::select("SELECT a.*,b.color, b.name as business_type_name, c.name as baranggay_name, d.name 
                                FROM baranggay_record_table a 
                                LEFT JOIN business_table b on b.id = a.business_type 
                                LEFT JOIN baranggay_table c ON c.id = a.baranngay 
                                LEFT JOIN biz_sec_table d ON d.id = a.biz_sec
                                WHERE d.id = $biz_sec 
                                ORDER BY a.id DESC");
        }
        else{
            $data = DB::select("SELECT a.*,b.color, b.name as business_type_name, c.name as baranggay_name, d.name  FROM baranggay_record_table a LEFT JOIN business_table b on b.id = a.business_type LEFT JOIN baranggay_table c ON c.id = a.baranngay 
                LEFT JOIN biz_sec_table d ON d.id = a.biz_sec
                ORDER BY a.id DESC");
        }

        return $data;
    }



    public function get_facilities_record_for_map(Request $request){
        return DB::select("SELECT a.*, b.name as baranggay_name  FROM facilities_table a LEFT JOIN baranggay_table b ON a.baranngay = b.id");
    }

    public function get_population_records(Request $request){
        $id =  $request->id;
        if($id){
            $data = DB::select("SELECT population_table.group, SUM(COUNT) count
                                FROM population_table
                                WHERE baranngay = $id
                                GROUP BY population_table.group");
        }else{
            $data = DB::select("SELECT population_table.group, SUM(COUNT) count
                                FROM population_table
                                GROUP BY population_table.group");
        }

        return $data;
    }

    public function get_barangay_chart(Request $request){
        $id =  $request->id;
        if($id){
            $data = DB::select("SELECT b.name, COUNT(*) AS quantity, b.color
                    FROM baranggay_record_table a
                    LEFT JOIN business_table b ON a.business_type = b.id
                    WHERE a.baranngay = $id
                    GROUP BY a.business_type");
        }else{
            $data = DB::select("SELECT b.name, COUNT(*) AS quantity, b.color
                    FROM baranggay_record_table a
                    LEFT JOIN business_table b ON a.business_type = b.id
                    GROUP BY a.business_type");
        }
        $label = [];
        $set = [];
        $color = [];

        foreach ($data as $row) {
            array_push($label, $row->name);
            array_push($set, $row->quantity);
            array_push($color, $row->color);
        }

        return [
            "label" => $label,
            "set" => $set,
            "color" => $color
        ];
    }


    public function get_total_biz_per_baranggay(){
        $data = DB::select("SELECT COUNT(*) AS total, b.name, b.color
                FROM baranggay_record_table a
                LEFT JOIN baranggay_table b ON b.id = a.baranngay 
               GROUP BY a.baranngay");

        $label = [];
        $set = [];
        $color = [];

        foreach ($data as $row) {
            array_push($label, $row->name);
            array_push($set, $row->total);
            array_push($color, $row->color);
        }

        return [
            "label" => $label,
            "set" => $set,
            "color" => $color
        ];
    }


    public function population_chart(Request $request){
        $request->validate([
            "filter_year" => 'required'
        ]);

        $data = $request->all();
        $year =  $data['filter_year'];
        $barangay =  isset($data['filter_baranggay'])?$data['filter_baranggay']:false;


        $filter = "";
        if($barangay){
            $filter = " AND b.id =  $barangay";
        }


        $population =  DB::select("SELECT a.*,
                                    b.name
                                    FROM population_table a
                                    LEFT JOIN baranggay_table b ON b.id = a.baranngay
                                    WHERE a.year = '$year' $filter");


        $label = [];
        $male = [];
        $female = [];
        $sc_male = [];
        $sc_female = [];

        foreach ($population as $row) {
            array_push($label, $row->name);
            array_push($male, $row->male);
            array_push($female, $row->female);
            array_push($sc_male, $row->sc_male);
            array_push($sc_female, $row->sc_female);
        }

        return [
            "label" => $label,
            "datasets" => [
                [
                    "label" => "Male",
                    "data" => $male,
                    "borderColor" => "#ffa69e",
                    "backgroundColor" => "#ffa69e",
                ],
                [
                    "label" => "Female",
                    "data" => $female,
                    "borderColor" => "#faf3dd",
                    "backgroundColor" => "#faf3dd",
                ],
                [
                    "label" => "Senior Male",
                    "data" => $sc_male,
                    "borderColor" => "#b8f2e6",
                    "backgroundColor" => "#b8f2e6",
                ],
                [
                    "label" => "Senior Female",
                    "data" => $sc_female,
                    "borderColor" => "#aed9e0",
                    "backgroundColor" => "#aed9e0",
                ],
            ],
        ];


    }

    public function get_agri_chart(){
        $data = DB::select("SELECT b.name as agri_type_name, SUM(a.produced) AS produced
                            FROM agriculture_table a 
                            LEFT JOIN agri_type_table b on b.id = a.agri_type 
                            GROUP BY b.name");

        $label = [];
        $set = [];
        $color = ["red", "blue", "yellow"];

        foreach ($data as $row) {
            array_push($label, $row->agri_type_name);
            array_push($set, $row->produced);
        }

        return [
            "label" => $label,
            "set" => $set,
            "color" => $color
        ];


    }



    public function get_all_barangays(Request $request){
        $id = $request->id;
        if($id){
            return DB::select("SELECT * FROM baranggay_table WHERE id = $id");
        }else{
            return DB::select("SELECT * FROM baranggay_table");
        }
    }



    public function delete_rec(Request $request){
        $data = $request->all();
        $table = "";
        $record_id = $data['id'];
        $table_id =  $data['table_id'];


        if($table_id == 1){
            $table = "baranggay_record_table";
        }else if($table_id == 2){
            $table = "agriculture_table";
        }else if($table_id == 3){
            $table = "population_table";
        }else if($table_id == 4){
            $table = "facilities_table";
        }

        DB::table($table)->where('id', $record_id)->delete();
    }
}
