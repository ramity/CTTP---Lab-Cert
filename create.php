<?php
//START DEFINED POST VARIABLES
require_once('bend/configmanager.php');
require_once('bend/pass.php');
require_once('bend/modules/auth.php');
require_once('bend/modules/protected.php');
require_once('bend/modules/forms.php');
require_once('bend/modules/input_functions.php');
//END DEFINED POST VARIABLES
function width($text,$xl)
{
  if(empty($text))
  {
    $box = imagettfbbox(15,0,'font/arial.ttf',$xl);
    return $text_width = $box[0] + $box[2];
  }
  {
    $box = imagettfbbox(15,0,'font/arial.ttf',$text);
    return $text_width = $box[0] + $box[2];
  }
}

function conv_js_string($input)
{
  $input = str_replace(array("\n","\r"),' ',$input);
  $input = htmlentities($input,ENT_QUOTES);
  return $input;
}
?>
<!DOCTYPE html>
  <head>
    <title>CTTP Calibration Application</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet" type="text/css">
    <link rel="stylesheet" type="text/css" href="css/main.css">
    <link rel="stylesheet" type="text/css" href="css/create.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.2/jquery.min.js"></script>

    <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>

    <script src="js/keydrown.min.js"></script>
    <script src="js/create.js"></script>

    <!--icon links-->
    <link rel="icon" href="img/caliper.ico">

  </head>
  <body>
    <?php require_once('bend/blocks/banner.php');?>
    <div id="container-touch">
      <form id="filebar" action="create.php" method="post">
        <?php
        echo '<select class="plate" name="file_year">';
        echo '<option disabled selected value>Year</option>';
        foreach($o=scandir("bend/file_storage/AMRL Equipment Calibrations/") as $oitem)
        {
          if(strpos($oitem,'Equipment Calibrations')!==false)
          {
            $bits=explode(' Equipment Calibrations',$oitem);
            if(isset($_POST['file_year'])&&!empty($_POST['file_year'])&&$_POST['file_year']==$bits[0])
            {
              echo '<option value="'.$bits[0].'" selected>'.$bits[0].'</option>';
            }
            else
            {
              echo '<option value="'.$bits[0].'">'.$bits[0].'</option>';
            }
          }
        }
        echo '</select>';
        if(isset($_POST['file_year'])&&!empty($_POST['file_year']))
        {
          echo '<select class="plate" name="file_name">';
          echo '<option disabled selected value>File Name</option>';
          foreach($o=scandir("bend/file_storage/AMRL Equipment Calibrations/".$_POST['file_year']." Equipment Calibrations/") as $oitem)
          {
            if($oitem!=='.'&&$oitem!=='..'&&substr($oitem,-4)!='.pdf')
            {
              if(isset($_POST['file_name'])&&!empty($_POST['file_name'])&&$_POST['file_name']==$oitem)
              {
                echo '<option value="'.$oitem.'" selected>'.$oitem.'</option>';
              }
              else
              {
                echo '<option value="'.$oitem.'">'.$oitem.'</option>';
              }
            }
          }
          echo '</select>';
        }
        if(isset($_POST['file_name'])&&!empty($_POST['file_name']))
        {
          $path="bend/file_storage/AMRL Equipment Calibrations/".$_POST['file_year']." Equipment Calibrations/".$_POST['file_name'];
          echo '<select class="plate" name="sheet_selection">';
          require_once('bend/PHPExcelReader/PHPExcel/IOFactory.php');
          try
          {
            $objPHPExcel = PHPExcel_IOFactory::load($path);
          }
          catch(Exception $e)
          {
            die('Error loading file "'.pathinfo($path,PATHINFO_BASENAME).'": '.$e->getMessage());
          }
          $sheets=$objPHPExcel->getSheetNames();
          echo '<option disabled selected value>Sheet Name</option>';
          foreach($sheets as $data)
          {
            if(isset($_POST['sheet_selection'])&&!empty($_POST['sheet_selection'])&&$_POST['sheet_selection']==str_replace('"','&',$data))
            {
              echo '<option value="'.str_replace('"','&',$data).'" selected>'.$data.'</option>';
            }
            else
            {
              echo '<option value="'.str_replace('"','&',$data).'">'.$data.'</option>';
            }
          }
          echo '</select>';
        }
        ?>
        <input class="submit" type="submit" name="form-submit">
        <?php
        if(isset($_POST['file_year'])&&!empty($_POST['file_year']))
        {
          echo '<a href="create.php"><div class="clearbutton">Reset Query</div></a>';
        }
        ?>
      </form>
      <?php
      if(isset($_POST['sheet_selection'])&&!empty($_POST['sheet_selection']))
      {
        if(strpos($_POST['sheet_selection'],'&')!==false)
        {
          $_POST['sheet_selection']=str_replace('&','"',$_POST['sheet_selection']);
        }

        $sheetData = $objPHPExcel->getSheetByName($_POST['sheet_selection'])->toArray(null,true,true,true);
        $max=0;
        $abc=range('A','Z');

        foreach($sheetData as $key=>$row)
        {
          if($max<=count($row))
          {
            $max=count($row);
          }
        }

        $cell_widths=array();

        echo '<script>';
        echo 'window.max='.$max.';';

        echo 'window.conv_array=[';
        foreach($sheetData as $key=>$row)
        {
          echo "[";
          for($z=0;$z<$max;$z++)
          {
            if(isset($row[$abc[$z]]))
            {
              $cell_widths[$key][$z]=width(conv_js_string($row[$abc[$z]]),$abc[$z]);
            }
            else
            {
              $cell_widths[$key][$z]=width('',$abc[$z]);
            }

            if($z==$max-1)
            {
              if(!empty($row[$abc[$z]]))
              {
                echo '[';
                  echo '"'.conv_js_string($row[$abc[$z]]).'"';
                echo ']],';
              }
              else
              {
                echo '[]],';
              }
            }
            else
            {
              if(!empty($row[$abc[$z]]))
              {
                echo '[';
                echo '"'.conv_js_string($row[$abc[$z]]).'"';
                echo '],';
              }
              else
              {
                echo '[],';
              }
            }
          }
        }
        echo '];';

        $cell_widths_max=array();
        $cell_widths_padding=10;

        foreach($cell_widths as $row)
        {
          for($x=0;$x<count($row);$x++)
          {
            if(empty($cell_widths_max[$x]))
            {
              $cell_widths_max[$x]=$row[$x]+$cell_widths_padding;
            }
            else
            {
              if($row[$x]>$cell_widths_max[$x])
              {
                $cell_widths_max[$x]=$row[$x]+$cell_widths_padding;
              }
            }
          }
        }

        echo 'window.col_widths=[';

        $col_width=0;

        for($x=0;$x<count($cell_widths_max);$x++)
        {
          if($x==count($cell_widths_max)-1)
          {
            $col_width+=$cell_widths_max[$x];

            echo "['".$cell_widths_max[$x]."']";
          }
          else
          {
            $col_width+=$cell_widths_max[$x];

            echo "['".$cell_widths_max[$x]."'],";
          }
        }

        echo '];';

        echo 'window.canvas_width='.$col_width.';';
        echo 'window.canvas_height='.(count($sheetData)*25).';';

        echo '</script>';
      ?>
      <div id="spreadsheet_main">
        <div id="spacer"></div>
        <div id="col_holder" <?php echo 'style="width:'.$col_width.'px"';?>>
          <?php
          for($x=0;$x<count($cell_widths_max);$x++)
          {
            echo '<div class="col_item" id="col_header_'.$abc[$x].'" style="width:'.($cell_widths_max[$x]-1).'px">'.$abc[$x].'</div>';
          }
          ?>
        </div>
        <div id="row_holder" <?php echo 'style="height:'.(count($sheetData)*25).'px"';?>>
          <?php
          for($x=1;$x<count($sheetData)+1;$x++)
          {
            echo '<div class="row_item" id="row_header_'.$x.'">'.$x.'</div>';
          }
          ?>
        </div>
        <div id="spreadsheet_holder">
          <canvas id="container">Your browser does not support the canvas tag.</canvas>
        </div>
        <div id="menubar">
          <div id="menubar_mouse">x 0 : y 0</div>
          <div id="menubar_selection_mode">Selection mode : 1</div>
          <div id="toolbar_button"></div>
        </div>
        <div id="toolbar">
          <div id="toolbar_tabs">
            <div class="toolbar_tab_item">Create</div>
            <div class="toolbar_tab_item">Debug</div>
            <div class="toolbar_tab_item">Settings</div>
            <div id="toolbar_resizebar"></div>
          </div>
          <div id="toolbar_holders">
            <div class="toolbar_holder" id="toolbar_holder_create"></div>
            <div class="toolbar_holder" id="toolbar_holder_debug"></div>
            <div class="toolbar_holder" id="toolbar_holder_settings">
              <div class="toolbar_"
            </div>
          </div>
        </div>
      </div>
      <?php
      }
      ?>
    </div>
  </body>
</html>
