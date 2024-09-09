@extends('layouts.main')

@section('title', 'Yazaroğlu Elektronik')

@section('content')

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-12">
          <h1 class="m-0 p-0" style="line-height: 1.75;">Görevler</h1>
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
          <div class="card">
            <div class="card-body">
              <table class="table table-hover display nowrap w-100" style="font-size: 0.9rem;" id="gorevler-table">
                <thead>
                  <tr class="text-center">
                    <th>MPS</th>
                    <th class="text-left">Üretim Başlığı</th>
                    <th>Stok Kodu</th>
                    <th>Adet</th>
                    <th>Oluşturan</th>
                    <th>Durum</th>
                    <th>İşlemler</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($records as $record)
                    <tr>
                      <td class="text-center">{{ $record->mps }}</td>
                      <td>{{ $record->title }}</td>
                      <td class="text-center">{{ $record->stock_code }}</td>
                      <td class="text-center">{{ $record->quantity }}</td>
                      <td class="text-center">{{ $record->fullname }}</td>
                      <td class="text-center">{{ $record->status }}</td>
                      <td>
                        <div class="d-flex justify-content-center flex-column flex-md-row">
                          <button type="button" class="btn btn-sm btn-info mx-1 mb-1 mb-md-0" onClick="inspect('{{$record->mps}}')" data-toggle="modal" data-target="#modal-onizleme">
                            <i class="fa fa-bars"></i> Detay</button>
                          @if($record->status == "Bitirildi")
                            <button type="button" class="btn btn-sm btn-success mx-1 mb-1 mb-md-0" onClick="approveProduction('{{$record->mps}}')">
                              <i class="fa fa-check"></i> Onayla</button>
                          @endif
                          <button type="button" class="btn btn-sm btn-danger mx-1 mb-1 mb-md-0" onClick="deleteTask('{{$record->mps}}')">
                              <i class="fa fa-trash"></i> İptal Et</button>
                        </div>
                      </td>
                    </tr>
                  @endforeach
                  
                </tbody>
              </table>
            </div>
          </div>
          <!-- /.card -->
        </div>
      </div>
    </div>
    <!-- /.container-fluid -->


    <!-- Güncelle Modal -->
    <div class="modal fade" id="modal-guncelle">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalGuncelleLabel">Üretim Düzenle</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <form action="{{ route('productions.update') }}" method="POST" autocomplete="off">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                  <label>Üretim Başlığı</label>
                  <input type="text" class="form-control" name="title" id="edit-title">
                  <label style="margin-top: 10px;">Stok Kodu</label>
                  <input type="text" class="form-control" name="stock_code" id="edit-stock_code">
                  <input type="hidden" name="id" id="edit-id">
                </div>
                <div>
                  <table class="table table-bordered">
                    <thead>
                      <tr>
                        <th>Adım</th>
                        <th>Sn / Adet</th>
                        <th>Sil</th>
                      </tr>
                    </thead>
                    <tbody id="edit-steps">

                    </tbody>
                  </table>
                  <div class="d-flex justify-content-end">
                    <button type="button" class="btn btn-outline-dark" onClick="addStep(this)">Adım Ekle</button>
                  </div>
                </div>
              
            </div>
            <div class="modal-footer justify-content-between">
              <button type="button" class="btn btn-default" data-dismiss="modal">Kapat</button>
              <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <!-- /.modal -->

    <!-- Önizleme Modal -->
    <div class="modal fade" id="modal-onizleme">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Görev İncele</h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="card-body">
              <form>
                <div class="row">
                  <div class="col-sm-12">
                    <div id="inspect-task">
                      <p id="mps"></p>

                      <p id="production-title"></p>

                      <p id="stock_code"></p>

                      <p id="quantity"></p>

                      <p id="controller-fullname"></p>

                      <label>Adımlar:</label>
                      <div id="records">
                        
                      </div>
                    </div>
                  </div>
                </div>
              </form>
            </div>
          </div>
          <div class="modal-footer justify-content-end">
            <button type="button" class="btn btn-default" data-dismiss="modal">Kapat</button>
          </div>
        </div>
        <!-- /.modal-content -->
      </div>
      <!-- /.modal-dialog -->
    </div>




  </section>
  <!-- /.content -->
</div>

<script>
  function inspect(mps){
    $('#records').empty();

    $.ajax({
      url: '/Tasks/' + mps,
      type: 'GET',
      success: function (response) {
        console.log(response);

        $('#inspect-task #mps').html("<strong> MPS: </strong> " + mps);

        $('#inspect-task #production-title').html("<strong> Üretim Başlığı: </strong> " + response[0].step.production.title);

        if (response[0].step.production.stock_code != null)
         $('#inspect-task #stock_code').html("<strong> Stok Kodu: </strong> " + response[0].step.production.stock_code);

        $('#inspect-task #quantity').html("<strong> Üretim Adeti: </strong> " + response[0].quantity);

        $('#inspect-task #controller-fullname').html("<strong> Kontrolcü: </strong> " + response[0].controller.fullname);

        response.forEach(function (record) {
          record.end = "";

          if (record.status == "Tamamlandı")
            record.end = record.updated_at;

          if (record.start == null)
            record.start = "";

          if (record.elapsed_time == null)
            record.elapsed_time = "";

          if (record.description == null)
            record.description = "";

          let div = `
            <div class="border border-dark rounded p-3 mb-3">
              <p><strong>Adım:</strong> ${record.step.title}</p>
              <p><strong>Çalışanlar:</strong> ${record.employees}</p>
              <p><strong>Başlangıç:</strong> ${formatDate(record.start)}</p>
              <p><strong>Bitiş:</strong> ${formatDate(record.updated_at)}</p>
              <p><strong>Geçen Süre:</strong> ${formatElapsedTime(record.elapsed_time)}</p>
              <p><strong>Durum:</strong> ${record.status}</p>
              <div><strong>Açıklama:</strong> ${record.description}</div>
            </div>
          `;

          $('#records').append(div);
        });
      },
      error: function (xhr) {
        alert('An error occurred while fetching the records.');
      }
    });
  }

  function deleteTask(mps){
    if(confirm(mps + " MPS numaralı üretimi silmek istediğinize emin misiniz?")){
      $.ajax({
        url: '/DeleteRecords/' + mps,
        method: 'DELETE',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
          location.reload();
        },
        error: function(xhr) {
          alert('An error occurred: ' + xhr.responseJSON.message);
        }
      });
    }
  }

  function approveProduction(mps){
    if(confirm(mps + " MPS numaralı üretimi onaylamak istediğinize emin misiniz?")){
      $.ajax({
        url: '/ApproveProduction/' + mps,
        type: 'GET',
        data: {
          _token: '{{ csrf_token() }}'
        },
        success: function (response) {
          location.reload();
        },
        error: function (xhr, status, error) {
          console.log('Error: ' + error);
          alert('An error occurred while approving the production.');
        }
      });
    }
  }
</script>

@endsection