@extends('layouts.main')

@section('title', 'Yazaroğlu Elektronik')

@section('content')

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-12">
          <h1 class="m-0 p-0" style="line-height: 1.75;">Görevlerim</h1>
        </div><!-- /.col -->
      </div><!-- /.row -->
    </div><!-- /.container-fluid -->
  </div>
  <!-- /.content-header -->

  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-12">

              @foreach ($groupedRecords as $mps => $records)
                <table class="table table-hover bg-white rounded">
                  <tbody>
                    <tr data-widget="expandable-table" aria-expanded="false">
                      <td class="border-0 rounded text-lg">
                        <i class="expandable-table-caret fas fa-caret-right fa-fw"></i>
                        {{ $records[0]->step->production->title }}
                      </td>
                    </tr>
                    <tr class="expandable-body d-none">
                      <td>
                        <div>
                          <p><strong>MPS: </strong>{{ $records[0]->mps }}</p>
                          <p><strong>Üretim Başlığı: </strong>{{ $records[0]->step->production->title }}</p>
                          <p><strong>Stok Kodu: </strong>{{ $records[0]->step->production->stock_code }}</p>
                          <p><strong>Tarih: </strong>{{ $records[0]->created_at->isoFormat('D MMMM YYYY HH:mm') }}</p>
                          <p><strong>Adımlar: </strong></p>
                          @foreach ($records as $record)
                            <table class="table table-hover bg-white rounded">
                              <tbody>
                                <tr data-widget="expandable-table" aria-expanded="false">
                                  <td class="border-0 rounded text-lg">
                                    <i class="expandable-table-caret fas fa-caret-right fa-fw"></i>
                                    {{ $record->step->title }}
                                  </td>
                                </tr>
                                <tr class="expandable-body d-none">
                                  <td>
                                    <div>
                                      <p><strong>Adım: </strong>{{ $record->step->title }}</p>
                                      <p><strong>Adet: </strong>{{ $record->quantity }}</p>
                                      <p><strong>Çalışanlar: </strong>{{ $record->employees }}</p>
                                      @if ($record->description != null)
                                        <p><strong>Açıklama: </strong>{{ $record->description }}</p>
                                      @endif
                                      <p><strong>Durum: </strong>{{ $record->status }}</p>

                                      <div class="d-flex flex-column flex-md-row">
                                        @if ($record->status == "Başlamadı")
                                          <a href="/StartTask/{{ $record->id }}" class="btn btn-success mx-1 mb-1 mb-md-0">
                                            <i class="fa fa-play"></i> Başla</a>
                                        @elseif ($record->status == "Devam Ediyor")
                                          <a href="/PauseTask/{{ $record->id }}" class="btn btn-warning mx-1 mb-1 mb-md-0">
                                            <i class="fa fa-pause"></i> Duraklat</a>
                                          <a href="/EndTask/{{ $record->id }}" class="btn btn-danger mx-1 mb-1 mb-md-0" onclick="return confirm('{{ $record->step->title }} adımını bitirmek istediğinizden emin misiniz?');">
                                            <i class="fa fa-stop"></i> Bitir</a>
                                        @elseif ($record->status == "Duraklatıldı")
                                          <a href="/StartTask/{{ $record->id }}" class="btn btn-success mx-1 mb-1 mb-md-0">
                                            <i class="fa fa-play"></i> Devam Et</a>
                                          <a href="/EndTask/{{ $record->id }}" class="btn btn-danger mx-1 mb-1 mb-md-0" onclick="return confirm('{{ $record->step->title }} adımını bitirmek istediğinizden emin misiniz?');">
                                            <i class="fa fa-stop"></i> Bitir</a>
                                        @endif
                                      </div>
                                    </div>  
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                            @endforeach
                          
                          </div>
                        </div>  
                      </td>
                    </tr>
                  </tbody>
                </table>
              @endforeach

        </div>
      </div>
    </div>
    <!-- /.container-fluid -->
  </section>
  <!-- /.content -->
</div>

@endsection