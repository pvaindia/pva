<?php

namespace App\Http\Controllers;

use App\AnswerSheet;
use App\Customer;
use App\Mail\AnswerSheetSubmitMail;
use App\Question;
use App\StudentChild;
use App\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Illuminate\Database\QueryException;
use Auth;
use Session;
use File;
use ZipArchive;
use Yajra\DataTables\Contracts\DataTable;

class ExamController extends Controller
{

        public function question_paper_panel(Request $request){


            if(request()->ajax())
            {
                if(!empty($request->from_date))
                {
                    $from_date = date('Y-m-d', strtotime($request->from_date));
                    $upto_date = date('Y-m-d', strtotime($request->to_date));
                    return datatables()->of(Question::whereBetween('issue_date', array($from_date, $upto_date))->get())

                        ->addColumn('course_code', function ($data) {
                            $courseCode =$data->belongsToCourse->course_code;
                            $courseName =$data->belongsToCourse->course_name;
                            $cCodeCname=$courseCode.'-'.$courseName;
                            return $cCodeCname;
                        })
                        ->rawColumns(['course_code'])

                        ->addColumn('subject_code', function ($data) {
                            $subjectCode =$data->belongsToSubject->subject_code;
                            $subjectName =$data->belongsToSubject->subject_name;
                            $sCodeSname=$subjectCode.'-'.$subjectName;
                            return $sCodeSname;
                        })
                        ->rawColumns(['subject_code'])

                        ->addColumn('issue_date', function ($data) {
                            $examstartdate =date( 'd-m-Y', strtotime($data->issue_date));
                            return $examstartdate;
                        })
                        ->rawColumns(['issue_date'])

                        ->addColumn('issue_time', function ($data) {
                            $examstarttime =date("g:i a", strtotime($data->issue_time));
                            return $examstarttime;
                        })
                        ->rawColumns(['issue_time'])

                        ->addColumn('expiry_date', function ($data) {
                            $examstartdate =date( 'd-m-Y', strtotime($data->expiry_date));
                            return $examstartdate;
                        })
                        ->rawColumns(['expiry_date'])

                        ->addColumn('expiry_time', function ($data) {
                            $examstarttime =date("g:i a", strtotime($data->expiry_time));
                            return $examstarttime;
                        })
                        ->rawColumns(['expiry_time'])

                        ->addColumn('action', function ($data) {
                            $edit = route('edit_question_paper', $data->id);
                            $delete = route('delete_question_paper', $data->id);
                            $button = "&emsp;<a href='{$edit}' title='Update Answer' class='btn btn-sm btn-default ' ><i class='splashy-document_letter_edit'></i></a>";
                            $button .= '&nbsp;&nbsp;';
                            $button .= "&emsp;<a href='{$delete}' title='Cancel' class='btn btn-sm btn-default ' onclick='return ConfirmDelete()' ><i class='splashy-document_letter_remove'></i></a>";
                            return $button;
                        })
                        ->rawColumns(['action'])
                        ->make(true);
                }
                else

                {

                         return datatables()->of(Question::where('issue_date', '=', date('Y-m-d'))->get())

                             ->addColumn('course_code', function ($data) {
                                 $courseCode =$data->belongsToCourse->course_code;
                                 $courseName =$data->belongsToCourse->course_name;
                                 $cCodeCname=$courseCode.'-'.$courseName;
                                 return $cCodeCname;
                             })
                             ->rawColumns(['course_code'])

                             ->addColumn('subject_code', function ($data) {
                                 $subjectCode =$data->belongsToSubject->subject_code;
                                 $subjectName =$data->belongsToSubject->subject_name;
                                 $sCodeSname=$subjectCode.'-'.$subjectName;
                                 return $sCodeSname;
                             })
                             ->rawColumns(['subject_code'])

                             ->addColumn('issue_date', function ($data) {
                                $examstartdate =date( 'd-m-Y', strtotime($data->issue_date));
                                 return $examstartdate;
                             })
                             ->rawColumns(['issue_date'])

                             ->addColumn('issue_time', function ($data) {
                                 $examstarttime =date("g:i a", strtotime($data->issue_time));
                                 return $examstarttime;
                             })
                             ->rawColumns(['issue_time'])

                             ->addColumn('expiry_date', function ($data) {
                                 $examstartdate =date( 'd-m-Y', strtotime($data->expiry_date));
                                 return $examstartdate;
                             })
                             ->rawColumns(['expiry_date'])

                             ->addColumn('expiry_time', function ($data) {
                                 $examstarttime =date("g:i a", strtotime($data->expiry_time));
                                 return $examstarttime;
                             })
                             ->rawColumns(['expiry_time'])

                        ->addColumn('action', function ($data) {
                            $edit = route('edit_question_paper', $data->id);
                            $delete = route('delete_question_paper', $data->id);
                            $button = "&emsp;<a href='{$edit}' title='Update Answer' class='btn btn-sm btn-default ' ><i class='splashy-document_letter_edit'></i></a>";
                            $button .= '&nbsp;&nbsp;';
                            $button .= "&emsp;<a href='{$delete}' title='Cancel' class='btn btn-sm btn-default ' onclick='return ConfirmDelete()' ><i class='splashy-document_letter_remove'></i></a>";
                            return $button;
                        })
                        ->rawColumns(['action'])
                        ->make(true);
                }

            }

            return View::make('exam/question_paper/question_paper_panel');



    }
    public function new_question_paper(){
        return View::make('exam/question_paper/new_question_paper');
    }
    public function store_question_paper(Request $request){


        $issueDate = explode('-', $request->input('issue_date'));
        $issueDay = $issueDate[0];
        $issueMonth = $issueDate[1];
        $issueYear =  $issueDate[2];

        $expiryDate = explode('-', $request->input('expiry_date'));
        $expiryDay = $expiryDate[0];
        $expiryMonth = $expiryDate[1];
        $expiryYear =  $expiryDate[2];

        $storeQuestionPaper = New Question();

        $storeQuestionPaper->teacher_id=$request->teacher_id;
//        $storeQuestionPaper->exam_title=$request->exam_title;
        $storeQuestionPaper->course_id=$request->course_id;

        if($request->subject_id !==""){
            $storeQuestionPaper->subject_id=$request->subject_id;
            $subjectCode = Subject::find($storeQuestionPaper->subject_id);
            $storeQuestionPaper->exam_title = $subjectCode->subject_code;
        }

        $storeQuestionPaper->issue_date = date($issueYear . '-' . $issueMonth . '-' . $issueDay);
        $storeQuestionPaper->issue_time=$request->issue_time;
        $storeQuestionPaper->expiry_date = date($expiryYear . '-' . $expiryMonth . '-' . $expiryDay);
        $storeQuestionPaper->expiry_time=$request->expiry_time;

        $this->validate($request, [
            'pdf' => 'required|mimes:doc,docx',
        ]);

        $image = $request->file('pdf');
        $slug = str_slug($storeQuestionPaper->exam_title);

        if (isset($image))
        {
            $currentDate = Carbon::now()->toDateString();
            $imagename = $slug . '-' . $currentDate . '-' . uniqid() . '.' . $image->getClientOriginalExtension();

            if (!file_exists('QuestionPaper')) {
                mkdir('QuestionPaper', 0777, true);
            }
            $image->move('QuestionPaper', $imagename);
        }else{
            $imagename ='principal.jpg';
        }

        $storeQuestionPaper->pdf_path=$imagename;



        $storeQuestionPaper->save();
        Session::flash('flash_message', 'Successfully Saved!');
        return redirect()->route('new_question_paper');
    }

    public function AjaxCourseSubject($id){
        $cities = Subject::where("semester_id",$id)->pluck("id","subject_name");
        return json_encode($cities);
    }
    public function edit_question_paper($id){
        $editdata = Question::find($id);
        return View::make('exam/question_paper/edit_question_paper')->with('edit_question',$editdata);
    }
    public function update_question_paper(Request $request, $id ){

        $issueDate = explode('-', $request->input('issue_date'));
        $issueDay = $issueDate[0];
        $issueMonth = $issueDate[1];
        $issueYear =  $issueDate[2];

        $expiryDate = explode('-', $request->input('expiry_date'));
        $expiryDay = $expiryDate[0];
        $expiryMonth = $expiryDate[1];
        $expiryYear =  $expiryDate[2];

        $updatelatest=Question::find($id);

        $updatelatest->teacher_id=$request->teacher_id;
//        $updatelatest->exam_title=$request->exam_title;
        $updatelatest->course_id=$request->course_id;

        if($request->subject_id !==""){
            $updatelatest->subject_id=$request->subject_id;
            $subjectCode = Subject::find($updatelatest->subject_id);
            $updatelatest->exam_title = $subjectCode->subject_code;
        }

//        $updatelatest->subject_id=$request->subject_id;
        $updatelatest->issue_date = date($issueYear . '-' . $issueMonth . '-' . $issueDay);
        $updatelatest->issue_time=$request->issue_time;
        $updatelatest->expiry_date = date($expiryYear . '-' . $expiryMonth . '-' . $expiryDay);
        $updatelatest->expiry_time=$request->expiry_time;

        $image = $request->file('pdf');
        $slug = str_slug($updatelatest->exam_title);

        if (isset($image))
        {
            $currentDate = Carbon::now()->toDateString();
            $imagename = $slug . '-' . $currentDate . '-' . uniqid() . '.' . $image->getClientOriginalExtension();

            if (!file_exists('QuestionPaper')) {
                mkdir('QuestionPaper', 0777, true);
            }
            unlink('QuestionPaper/'.$updatelatest->pdf_path);
            $image->move('QuestionPaper', $imagename);
        }else{
            $imagename =$updatelatest->pdf_path;
        }


        $updatelatest->pdf_path=$imagename;
        $updatelatest->save();
        Session::flash('flash_message', 'Successfully updated!');
        return redirect()->route('question_paper_panel');

    }
    public function delete_question_paper($id)
    {
        $deletelatestupdate= Question::find($id);
        if(file_exists('QuestionPaper/'.$deletelatestupdate->pdf_path))
        {
            unlink('QuestionPaper/'.$deletelatestupdate->pdf_path);
        }
        $deletelatestupdate->delete();
        return redirect()->back()->with('successMsg','Data Successfully Deleted');

    }

    //Student Pending Question Paper
    public function pending_question(){
        if (Auth::guard('customer')->user()) {

            $studentChild = \App\StudentChild::where([
                ['student_id', '=', Auth::guard('customer')->user()->id],

            ])->get();

            $questions_total = array();
            foreach ($studentChild as $studentChild_single) {
                $studentChild_datas = \App\Question::
                where('subject_id', '=', $studentChild_single->subject_id)
                    ->where('expiry_date', '>=', date('Y-m-d'))
                    ->where('expiry_time', '>=', date('H:i'))
                    ->orderBy('created_at', 'desc')->get();
                foreach ($studentChild_datas as $studentChild_data_single) {
                    $questions_total[] = $studentChild_data_single;
                }
            }
            return View::make('exam/question_paper/pending_question')->with('questionPaper', $questions_total);
        }else{
            return redirect()->route('home');
        }
    }


    public function new_answer_sheet($id){
        if (Auth::guard('customer')->user()) {
            $answersheet = Question::find($id);
            return View::make('exam/question_paper/new_answer_sheet')->with('answersheet', $answersheet);
        }else{
            return redirect()->route('home');
        }
    }
    public function store_answer_sheet(Request $request)
    {
        $isAvailable = false;
        $checkanswersheets = AnswerSheet::where('question_id', $request->question_id)
            ->where('student_id', $request->student_id)
            ->get();

        foreach ($checkanswersheets as $checkanswersheet) {
            if (($checkanswersheet->question_id = $request->question_id) && ($checkanswersheet->student_id = $request->student_id)) {
                $isAvailable = true;
            }
        }
        if($isAvailable){
//                echo "already";
//                echo "<pre>";
//                print_r($checkanswersheet);
            Session::flash('flash_message', 'Answer Sheet Already Uploaded !');
            return redirect()->route('pending_question');
//                die();
        } else {
//            $this->validate($request, [
//                'answer_pdf_path' => 'required|mimes:doc,docx,.doc,.docx,pdf',
//            ]);
            DB::beginTransaction();
            try {

                $error = "";
                $slug = str_slug($request->enrollment_no . $request->exam_title . $request->reg_no);

                if ($_FILES && $_FILES['answer_pdf_path']) {

                    if (!empty($_FILES['answer_pdf_path']['name'][0])) {

                        $zip = new ZipArchive();
                        $zip_name = getcwd() . "/AmswerSheet/". $slug . '-' . uniqid() . ".zip";
                        //triming the name
                        $filename = substr($zip_name, 44);

                        // Create a zip target
                        if ($zip->open($zip_name, ZipArchive::CREATE) !== TRUE) {
                            $error .= "Sorry ZIP creation is not working currently.<br/>";
                        }

                        $imageCount = count($_FILES['answer_pdf_path']['name']);
                        for($i=0;$i<$imageCount;$i++) {

                            if ($_FILES['answer_pdf_path']['tmp_name'][$i] == '') {
                                continue;
                            }
                            $newname = date('YmdHis', time()) . mt_rand() . '.jpg';

                            // Moving files to zip.
                            $zip->addFromString($_FILES['answer_pdf_path']['name'][$i], file_get_contents($_FILES['answer_pdf_path']['tmp_name'][$i]));

                            // moving files to the target folder.
                            move_uploaded_file($_FILES['answer_pdf_path']['tmp_name'][$i], './uploads/' . $newname);
                        }
                        $zip->close();

                        // Create HTML Link option to download zip
                        $success = basename($zip_name);
                    } else {
                        $error = '<strong>Error!! </strong> Please select a file.';
                    }
                }
                
                // echo $zip_name;
                // echo "<pre>";
                // print_r($filename);
                // die();

                $storeAnswer = New AnswerSheet();
                $storeAnswer->date = date("Y-m-d");
                $storeAnswer->question_table_id = $request->question_table_id;
                $storeAnswer->semester_id = $request->semester_id;
                $storeAnswer->semester_name = $request->semester_name;
                $storeAnswer->question_id = $request->question_id;
                $storeAnswer->exam_title = $request->exam_title;
                $storeAnswer->student_id = $request->student_id;
                $storeAnswer->student_name = $request->student_name;
                $storeAnswer->course_id = $request->course_id;
                $storeAnswer->enrollment_no = $request->enrollment_no;
                $storeAnswer->course_name = $request->course_name;
                $storeAnswer->subject_id = $request->subject_id;
                $storeAnswer->subject_name = $request->subject_name;

                $storeAnswer->answer_pdf_path = $filename;
                $storeAnswer->save();

                if(!empty($request->email))
                {
                    $course_code= $request->exam_title;
                    $roll_no= $request->enrollment_no;

                    Mail::to($request->email)
                        ->cc('snsiliguri@gmail.com')
                        ->send(new AnswerSheetSubmitMail($course_code,$roll_no));
                }

                DB::commit();

                Session::flash('flash_message', 'Uploaded Successfully!');
                return redirect()->route('pending_question');

            } catch (\Exception $e) {
                DB::rollback();
                return redirect()->back()->with('flash_message','System Error Please try again');
                // print_r($e);
            }
        }
    }


    //answer sheet for teacher
    public function answer_sheet_panel(Request $request)
    {

        return View::make('exam/answer_sheet/answer_sheet_panel');

    }

    public function ajax_answer_sheet(Request $request)
    {

        if(request()->ajax())
        {
            if(!empty($request->from_date))
            {
                $from_date = date('Y-m-d', strtotime($request->from_date));
                $upto_date = date('Y-m-d', strtotime($request->to_date));
                return datatables()->of(AnswerSheet::whereBetween('date', array($from_date, $upto_date))->get())

                    ->addColumn('date', function ($data) {
                        $examdate =date( 'd-m-Y', strtotime($data->date));
                        return $examdate;
                    })
                    ->rawColumns(['date'])

                    ->addColumn('course_name', function ($data) {
                        $courseCode =$data->belongsToCourse->course_code;
                        $courseName =$data->belongsToCourse->course_name;
                        $cCodeCname=$courseCode.'-'.$courseName;
                        return $cCodeCname;
                    })
                    ->rawColumns(['course_name'])

                    ->addColumn('action', function ($data) {
                        $edit = route('edit_answer_sheet', $data->id);
                        $delete = route('delete_answer_sheet', $data->id);
                        $button = "&emsp;<a href='{$edit}' title='Update Answer' class='btn btn-sm btn-default ' ><i class='splashy-document_letter_edit'></i></a>";
                        $button .= '&nbsp;&nbsp;';
                        $button .= "&emsp;<a href='{$delete}' title='Cancel' class='btn btn-sm btn-default ' onclick='return ConfirmDelete()' ><i class='splashy-document_letter_remove'></i></a>";
                        return $button;
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            }
            else
            {
                return datatables()->of(AnswerSheet::where('date', '=', date('Y-m-d'))->get())

                    ->addColumn('date', function ($data) {
                        $examdate =date( 'd-m-Y', strtotime($data->date));
                        return $examdate;
                    })
                    ->rawColumns(['date'])

                    ->addColumn('course_name', function ($data) {
                        $courseCode =$data->belongsToCourse->course_code;
                        $courseName =$data->belongsToCourse->course_name;
                        $cCodeCname=$courseCode.'-'.$courseName;
                        return $cCodeCname;
                    })

                    ->addColumn('action', function ($data) {
                        $edit = route('edit_answer_sheet', $data->id);
                        $delete = route('delete_answer_sheet', $data->id);
                        $button = "&emsp;<a href='{$edit}' title='Update Answer' class='btn btn-sm btn-default ' ><i class='splashy-document_letter_edit'></i></a>";
                        $button .= '&nbsp;&nbsp;';
                        $button .= "&emsp;<a href='{$delete}' title='Cancel' class='btn btn-sm btn-default ' onclick='return ConfirmDelete()' ><i class='splashy-document_letter_remove'></i></a>";
                        return $button;
                    })
                    ->rawColumns(['action'])

                    ->make(true);
            }

        }

        return View::make('exam/answer_sheet/answer_sheet_panel');

    }

    public function edit_answer_sheet($id){
        $editAnswer = AnswerSheet::find($id);
        return View::make('exam/answer_sheet/edit_answer_sheet')->with('edit_answer_sheet',$editAnswer);
    }

    public function delete_answer_sheet($id)
    {
        $deleteanswerSheet= AnswerSheet::find($id);
        if(file_exists('AmswerSheet/'.$deleteanswerSheet->answer_pdf_path))
        {
            unlink('AmswerSheet/'.$deleteanswerSheet->answer_pdf_path);
        }
        $deleteanswerSheet->delete();
        return redirect()->back()->with('successMsg','Data Successfully Deleted');

    }

    public function update_marks(Request $request, $id ){


        $updatelatest=AnswerSheet::find($id);

        $updatelatest->total_marks=$request->total_marks;
        $updatelatest->marks_updated_by_teacher=$request->marks_updated_by_teacher;

        $updatelatest->save();
        Session::flash('flash_message', 'Successfully updated!');
        return redirect()->route('answer_sheet_panel');

    }

    public function result_panel()
    {
        return View::make('exam/result/result_panel');
    }

    public function ajax_result(Request $request)
    {

        $columns = array(

            0 =>'enrollment_no',
            1 =>'student_name',
            2 =>'course_name',
            3 =>'subject_name',
            4 =>'exam_title',
            5 =>'total_marks',
            6=> 'action',

        );

        $totalData = AnswerSheet::count();
        $totalFiltered = $totalData;
        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        if(empty($request->input('search.value')))
        {
            $posts = AnswerSheet::offset($start)
                ->limit($limit)
                ->whereNotNull('total_marks')
                ->get();


        }
        else {
            $search = $request->input('search.value');

            $posts =  AnswerSheet::where('enrollment_no','LIKE',"%{$search}%")
                ->orWhere('student_name', 'LIKE',"%{$search}%")
                ->orWhere('course_name', 'LIKE',"%{$search}%")
                ->orWhere('subject_name', 'LIKE',"%{$search}%")
                ->orWhere('exam_title', 'LIKE',"%{$search}%")
                ->orWhere('total_marks', 'LIKE',"%{$search}%")
                ->offset($start)
                ->limit($limit)
                ->whereNotNull('total_marks')
                ->get();



            $totalFiltered =  AnswerSheet::where('enrollment_no','LIKE',"%{$search}%")
                ->orWhere('student_name', 'LIKE',"%{$search}%")
                ->orWhere('course_name', 'LIKE',"%{$search}%")
                ->orWhere('subject_name', 'LIKE',"%{$search}%")
                ->orWhere('exam_title', 'LIKE',"%{$search}%")
                ->orWhere('total_marks', 'LIKE',"%{$search}%")

                ->count();

        }

        $data = array();
        if(!empty($posts))
        {
            foreach ($posts as $post)
            {
                $enrollment=Customer::where('id',$post->student_id)->first();

                $edit =  route('edit_answer_sheet',$post->id);

                $nestedData['enrollment_no'] = $post->enrollment_no;
                $nestedData['student_name'] = $post->student_name;
                $nestedData['course_name'] = $post->course_name;
                $nestedData['subject_name'] = $post->subject_name;
                $nestedData['exam_title'] = $post->exam_title;
                $nestedData['total_marks'] = $post->total_marks;

                $nestedData['answer_pdf_path'] = "&emsp;<a target='_blank' href='AmswerSheet/".$post->answer_pdf_path."'  style='font-family: TimesNewRoman;color:black!important; text-transform: capitalize;text-decoration: underline;'>$post->exam_title</a>";
                $edit_btn = "&emsp;<a href='{$edit}' title='Update Answer' class='btn btn-sm btn-default ' ><i class='splashy-document_letter_edit'></i></a>";


                $nestedData['action'] =$edit_btn."";
                $data[] = $nestedData;
            }
        }

        $json_data = array(
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        );

        echo json_encode($json_data);

    }

    public function student_result_panel(){

        $student_result = AnswerSheet::whereNotNull('total_marks')
            ->where('course_id',Auth::guard('customer')->user()->course_code)
            ->where('student_id',Auth::guard('customer')->user()->id)
            ->where('semester_id',Auth::guard('customer')->user()->semester_id)
            ->get();

        return View::make('exam/result/student_result_panel')->with('result',$student_result);
    }
    public function single_result($id)
    {
        $viewResult=AnswerSheet::find($id);
        return View::make('exam/result/single_result')->with('viewSingleResult',$viewResult);
    }
}
