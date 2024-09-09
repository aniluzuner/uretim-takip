<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use App\Models\Production;
use App\Models\User;
use App\Models\ProductionStep;
use App\Models\ProductionRecord;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;
use App\Notifications\RecordsNotification;

class ProductionController extends Controller{
  public function Productions(){
    return view('Production/productions');
  }

  public function GetProductions(){
    $productions = Production::all();
    
    foreach ($productions as $production) {
      $production->job_count = ProductionRecord::join('production_steps', 'production_records.step_id', '=', 'production_steps.id')
        ->where('status', '!=', 'Onaylandı')
        ->where('production_steps.production_id', $production->id)
        ->distinct('production_records.mps')
        ->count('production_records.mps');
    }

    return DataTables::of($productions)->make(true);
  }

  public function AddProduction(Request $request){
    $validatedData = $request->validate([
      'title' => 'required|string|max:255',
      'stock_code' => 'nullable|string',
      'steps' => 'required|array',
      'steps.*.step_title' => 'required|string|max:255',
      'steps.*.step_time' => 'nullable|numeric',
    ]);

    $production = Production::create([
        'title' => $validatedData['title'],
        'stock_code' => $validatedData['stock_code']
    ]);

    foreach ($validatedData['steps'] as $step) {
      $this->AddProductionStep($production->id, $step);
    }

    return redirect()->route('uretim')->with('success', 'Üretim ve adımlar başarıyla kaydedildi.');
  }

  public function AddProductionStep($id, $step){
    ProductionStep::create([
      'production_id' => $id,
      'title' => $step['step_title'],
      'time' => $step['step_time'],
    ]);
  }

  public function GetProduction($productionId){
    $production = Production::with('steps')->find($productionId);

    if (!$production)
      return response()->json(['error' => 'Production not found.'], 404);
        
    return response()->json($production);
  }

  public function Update(Request $request){
    $validatedData = $request->validate([
      'id' => 'required|integer',
      'title' => 'required|string|max:255',
      'stock_code' => 'nullable|string',
      'steps' => 'required|array',
      'steps.*.step_title' => 'required|string|max:255',
      'steps.*.step_time' => 'nullable|numeric',
    ]);

    $production = Production::find($validatedData['id']);
    if (!$production) {
        return response()->json(['error' => 'Production not found.'], 404);
    }

    $production->update([
        'title' => $validatedData['title'],
        'stock_code' => $validatedData['stock_code']
    ]);

    ProductionStep::where('production_id', $validatedData['id'])->delete();

    foreach ($validatedData['steps'] as $step) {
        ProductionStep::create([
            'production_id' => $validatedData['id'],
            'title' => $step['step_title'],
            'time' => $step['step_time']
        ]);
    }

    return redirect()->route('uretim')->with('success', 'Üretim ve adımlar başarıyla güncellendi.');
  }

  public function Delete($productionId){
    $production = Production::find($productionId);
    if (!$production)
      return response()->json(['error' => 'Production not found.'], 404);

    //$production->steps()->delete();
    $production->delete();

    return response()->json(['success' => 'Üretim başarıyla silindi.']);
  }

  public function AddProductionRecord(Request $request){
    $mps = $request->input('mps');
    $quantity = $request->input('quantity');
    $controller_id = $request->input('controller_id');
    $taskSteps = $request->input('taskStep');
    
    foreach ($taskSteps as $taskStep) {
      $step_id = $taskStep['step_id'];
      $employees = $taskStep['employees'];
      $description = $taskStep['description'];

      foreach ($employees as $employeeFullName) {
        $user = User::where(DB::raw('LOWER(fullname)'), strtolower($employeeFullName))->first();
  
        if (!$user) {
          $username = str_replace(' ', '', strtolower($employeeFullName));
          $password = $username;
  
          $user = new User();
          $user->fullname = $employeeFullName;
          $user->username = $username;
          $user->password = $password;
          $user->role = "Çalışan";
          $user->save();
        }
      }

      $productionRecord = new ProductionRecord();
      $productionRecord->mps = $mps;
      $productionRecord->step_id = $step_id;
      $productionRecord->quantity = $quantity;
      $productionRecord->controller_id = $controller_id;
      $productionRecord->employees = implode(', ', $employees);
      $productionRecord->status = "Başlamadı";
      $productionRecord->description = $description;
      $productionRecord->save();
    }

    return redirect()->route('uretim')->with('success', 'Görev başarıyla atandı.');
  }

  public function GetProductionRecords(){
    $records = ProductionRecord::selectRaw(
        'MAX(production_records.id) as id, 
         production_records.mps, 
         MAX(production_records.quantity) as quantity, 
         CASE 
            WHEN SUM(CASE WHEN production_records.status = "Başlamadı" THEN 1 ELSE 0 END) = COUNT(*) THEN "Başlamadı"
            WHEN SUM(CASE WHEN production_records.status = "Bitirildi" THEN 1 ELSE 0 END) = COUNT(*) THEN "Bitirildi"
            WHEN SUM(CASE WHEN production_records.status = "Onaylandı" THEN 1 ELSE 0 END) = COUNT(*) THEN "Onaylandı"
            ELSE "Devam Ediyor"
         END as status, 
         productions.title, 
         productions.stock_code, 
         users.fullname'
    )
    ->join('production_steps', 'production_records.step_id', '=', 'production_steps.id')
    ->join('productions', 'production_steps.production_id', '=', 'productions.id')
    ->join('users', 'production_records.controller_id', '=', 'users.id')
    ->groupBy('production_records.mps', 'productions.title', 'productions.stock_code', 'users.fullname')
    ->orderBy('production_records.created_at', 'desc')
    ->get();
    
    return view('production/tasks', compact('records'));
  }

  public function GetRecordsByMps($mps){
    $records = ProductionRecord::where('mps', $mps)
      ->with(['step.production', 'controller'])
      ->get();

    return response()->json($records);
  }

  public function GetRecordsByUser($id){
    $user = User::find($id);

    if ($user->role != "Çalışan"){
      $records = ProductionRecord::where('controller_id', $id)
        ->with(['step.production'])
        ->orderBy('created_at', 'desc')
        ->get();

      $groupedRecords = $records->groupBy('mps');
    }
    else{
      $records = ProductionRecord::where('employees', 'like', '%'. $user->fullname .'%')
        ->with(['step.production', 'controller'])
        ->orderBy('created_at', 'desc')
        ->get();
      $groupedRecords = $records->groupBy('mps');
    }
      
    return view('production/tasksById', compact('groupedRecords'));
  }

  public function DeleteRecords($mps){
    $records = ProductionRecord::where('mps', $mps)->delete();

    if ($records) 
      return response()->json(['message' => 'Kayıtlar başarıyla silindi.'], 200);
    else 
      return response()->json(['message' => 'Kayıt bulunamadı.'], 404);
  }

  public function StartTask($id){
    $record = ProductionRecord::find($id);

    if ($record->start == null)
      $record->update(['status' => "Devam Ediyor", 'start' => now()]);
    else
      $record->update(['status' => "Devam Ediyor"]);

    $controllers = User::where('role', 'Kontrolcü')->get();

    foreach ($controllers as $controller) {
      $stepTitle = $record->step->title;

      $controller->notify(new RecordsNotification($record->mps, $record->step->production->title, "Başlatıldı", "$stepTitle adımı başlatıldı."));
    }

    return redirect()->route('GetRecordsByUser', ['id' => Auth::user()->id]);
  }

  public function PauseTask($id){
    $record = ProductionRecord::find($id);

    $record->update(['status' => "Duraklatıldı", 'elapsed_time' => abs(Carbon::now()->diffInSeconds(Carbon::parse($record->updated_at)))]);

    $controllers = User::where('role', 'Kontrolcü')->get();

    foreach ($controllers as $controller) {
      $stepTitle = $record->step->title;

      $controller->notify(new RecordsNotification($record->mps, $record->step->production->title, "Duraklatıldı", "$stepTitle adımı duraklatıldı."));
    }

    return redirect()->route('GetRecordsByUser', ['id' => Auth::user()->id]);
  }

  public function EndTask($id){
    $record = ProductionRecord::find($id);

    $record->update(['status' => "Bitirildi", 'elapsed_time' => abs(Carbon::now()->diffInSeconds(Carbon::parse($record->updated_at)))]);

    $controllers = User::where('role', 'Kontrolcü')->get();

    foreach ($controllers as $controller) {
      $stepTitle = $record->step->title;

      $controller->notify(new RecordsNotification($record->mps, $record->step->production->title, "Bitirildi", "$stepTitle adımı bitirildi."));
    }

    return redirect()->route('GetRecordsByUser', ['id' => Auth::user()->id]);
  }

  public function ApproveProduction($mps){
    $records = ProductionRecord::where('mps', $mps)->get();

    foreach ($records as $record) {
      $record->update(['status' => 'Onaylandı']);
    }

    $inspectors = User::where('role', 'Denetleyici')->get();

    foreach ($inspectors as $inspector) {
      $inspector->notify(new RecordsNotification($record->mps, $record->step->production->title, "Onaylandı", Auth::user()->fullname . " tarafından onaylandı ve verimlilik raporu oluşturuldu."));
    }

    return response()->json(['message' => 'Üretim başarıyla onaylandı.'], 200);
  }

  public function GetReports(){
    $records = ProductionRecord::with('step')
        ->orderBy('created_at', 'desc')
        ->get()
        ->groupBy('mps');

    $results = [];

    foreach ($records as $mps => $group) {
      $allApproved = true;

      foreach ($group as $record) {
        if ($record->status !== 'Onaylandı') {
          $allApproved = false;
          break;
        }
      }

      if ($allApproved) {
        foreach ($group as $record) {
          $stepTime = $record->step->time / count(explode(', ',$record->employees));
          $expectedTime = $record->quantity * $stepTime;
          $elapsedTime = $record->elapsed_time;

          if ($elapsedTime <= $expectedTime) 
            $efficiency = 100;
          elseif ($elapsedTime >= 1.5 * $expectedTime) 
              $efficiency = 0;
          else 
            $efficiency = 100 - ((($elapsedTime - $expectedTime) / ($expectedTime * 0.5)) * 100);
          
          if ($efficiency >= 80)
            $record->efficiencyColor = "#00FF00";
          elseif ($efficiency >= 60)
            $record->efficiencyColor = "#00FFFF";
          elseif ($efficiency >= 40)
            $record->efficiencyColor = "#FFFF00";
          elseif ($efficiency >= 20)
            $record->efficiencyColor = "#FF8400";
          else
            $record->efficiencyColor = "#FF0000";     
      
          $record->efficiencyPercentage = number_format($efficiency, 0);
        }

        $results[] = $group;
      }
    }


    return view('production/reports', compact('results'));
  }

  public function Notifications(){
    $user = Auth::user();
    $notifications = $user->notifications;

    return view('notifications', compact('notifications'));
  }
}
