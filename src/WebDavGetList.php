<?php
//转换字节数为其他单位 $byte:字节
$toByte = function($byte){
    $v = 'unknown';
    if($byte >= 1099511627776){
        $v = round($byte / 1099511627776  ,2) . 'TB';
    } elseif($byte >= 1073741824){
        $v = round($byte / 1073741824  ,2) . 'GB';
    } elseif($byte >= 1048576){
        $v = round($byte / 1048576 ,2) . 'MB';
    } elseif($byte >= 1024){
        $v = round($byte / 1024, 2) . 'KB';
    } else{
        $v = $byte . 'B';
    }
    return $v;
};
?>
<!doctype html>
<html lang="zh-CN">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/css/bootstrap.min.css" integrity="sha384-zCbKRCUGaJDkqS1kPbPd7TveP5iyJE0EjAuZQTgFLD2ylzuqKfdKlfG/eSrtxUkn" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.1/font/bootstrap-icons.css">
    <title>FM</title>
    <style>
      body{padding-top: 3.7rem;overflow-x: hidden;font-size: 0.8rem;}
      .navbar{font-size: 1rem;}
      .table-md th, .table-md td {padding: 0.4rem;}
      .table-md th{border-top:none;}
      .table-md a{margin-right: 0.5rem;font-size: 1rem;}
      @media (max-width: 992px) {
        body{padding-top: 6.5rem;}
        .fm-search{margin-top: 0.5rem !important;}
        .fm-search input{width: 70%;}
        .fm-search button{margin-bottom: 0 !important;width: 29%;margin-top: 0 !important;margin-left: 1%;}
        .table-md td:nth-child(2),.table-md th:nth-child(2){display: none;}
        .fm-logout{display: none}
      }
      .modal-header{padding: 0.5rem 1rem;}
      .modal-title{font-size: 1rem;}
      .modal-header .close{padding: 0.5rem 1rem;margin: -0.5rem -1rem -0.5rem auto;}
    </style>
  </head>
  <body>
    <nav class="navbar navbar-expand-lg fixed-top navbar-dark bg-info">
      <div class="navbar-collapse offcanvas-collapse" id="navbarsExampleDefault">
        <ol class="breadcrumb mr-auto" style="padding:0.5rem 1rem;margin-bottom:0;">
          <li class="breadcrumb-item"><a href="<?=$prefix?>/">Home</a></li>
            <?php $last = count($pathList)-1;foreach ($pathList as $k=>$item):?>
            <?php if($last==$k):?>
                    <li class="breadcrumb-item active" aria-current="page"><?=basename($item)?></li>
            <?php else:?>
                    <li class="breadcrumb-item"><a href="<?=$item?>"><?=basename($item)?></a></li>
            <?php endif;?>
            <?php endforeach;?>
        </ol>
        <form class="form-inline fm-search">
          <input class="form-control mr-sm-2" name="search" type="text" value="<?=$search?>" placeholder="Search" aria-label="Search">
          <button class="btn btn-success my-2 my-sm-0" type="submit">Search</button>
        </form>
          <form class="d-none form-inline fm-upload" action="?name=file" method="post" enctype="multipart/form-data">
              <input class="ml-2" style="font-size:1rem;line-height: 2rem;width: 5.2rem;" name="file" type="file" placeholder="上传文件">
              <button class="btn btn-success my-2 my-sm-0" type="submit">Upload</button>
          </form>
          <?php if(\WebDav\WebDav::$authUsers):?>
          <a href="?auth=logout" class="fm-logout btn btn-dark ml-2 my-sm-0">Logout</a>
          <?php endif;?>
      </div>
    </nav>

    <div class="container-fluid">
      <table class="table table-hover table-md">
        <thead>
        <tr>
          <th>Name</th><th>Date</th><th>Size</th><th>OP</th>
        </tr>
        </thead>
        <tbody id="fm-list">
        <?php foreach ($list as $item):
        ?>
        <tr>
          <td><?=$item['is_dir']?'<a href="'.$prefix.$item['path'].'"><span class="fm-name">'.$item['name'].'</span></a>':'<span class="fm-name">'.$item['name'].'</span>'?></td><td><?=date("Y/m/d H:i",$item['mtime'])?></td><td><?=$item['is_dir']?'':$toByte($item['size'])?></td>
          <td>
              <?php if(!$item['is_dir']):?>
            <a href="<?=$prefix.$item['path']?>?down=1" target="_blank"><i class="bi bi-arrow-down-circle" title="下载"></i></a>
            <a href="javascript:" data-toggle="modal" data-target="#qrModal" data-url="<?=$prefix.$item['path']?>?down=1"><i class="bi bi-qr-code-scan" title="二维码"></i></a>
            <a href="<?=$prefix.$item['path']?>" target="_blank"><i class="bi bi-eye" title="查看"></i></a>
          <?php endif;?>
          </td>
        </tr>
        <?php endforeach;?>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
        <div class="modal-dialog" style="max-width: 290px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="qrModalLabel">二维码</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/js/bootstrap.min.js" integrity="sha384-VHvPCCyXqtD5DqJeNxl2dtTyhF78xXNXdkwX1CZeRusQfRKp+tA7hAShOK/B/fQ2" crossorigin="anonymous"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/jquery.qrcode/1.0/jquery.qrcode.min.js"></script>
    <script>
        $('#qrModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget) // Button that triggered the modal
            var url = button.data('url') // Extract info from data-* attributes
            $(this).find('.modal-body').html('').qrcode(url)
        })
        var search = '<?=$search?>';
        if (search != '') {
            var re = new RegExp('(' + search + ')', 'ig')
            $('span.fm-name').each(function(){
                $(this).html($(this).html().replace(re, '<span class="bg-warning">$1</span>'))
            });
        }
    </script>
  </body>
</html>