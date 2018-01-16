<?php
$type = $_FILES['fileField']['type'];
$size = $_FILES['fileField']['size'];
$name = $_FILES['fileField']['name'];
$tmp_name = $_FILES['fileField']['tmp_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>BATTLESHIP GAMES</title>
<link rel="stylesheet" type ="text/css" href ="style1.css">
<script src="battleships.js"></script>

<script type="text/javascript">
	$(function (){
 
    function format_float(num, pos)
    {
        var size = Math.pow(10, pos);
        return Math.round(num * size) / size;
    }
 
    function preview(input) {
 
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            
            reader.onload = function (e) {
                $('.preview').attr('src', e.target.result);
                var KB = format_float(e.total / 1024, 2);
                $('.size').text("檔案大小：" + KB + " KB");
            }
 
            reader.readAsDataURL(input.files[0]);
        }
    }
 
    $("body").on("change", ".upl", function (){
        preview(this);
    })

    
})
     function sum(a,b){
        var c = a + b;
        alert('a = ' + a + ',b = ' + b + ' ,a+b = ' + c);
    }

    function myfuntion(){
       var i;
       var sum = 0;

       for(i=1;i<=16;i=i+2){
        sum = sum + i;
       }
       alert(i + '+'+sum);
  }
  function test(){
    var i,j;
    for(i=1;i<=3;i++){
        for(j=1;j<=3;j++){
           alert("*");
        }
       alert('</p>');
    }
  }
</script>
</head>
<body>
<A href='#' onclick="sum(5,6)">a+b</A></p>
<button onclick="myfuntion()">計算1+3+5+...+15</button>
<button onclick="test()">test</button>
<div class="title">
<h1><image src="PICTURE/startup.png" style="width: 30px;">BATTLESHIPS !</h1>
<div id ="messagearea"></div>
<table style = "border-color: black;" width = "400" border ="1" height = "400" align ="center">
<tr>
<td id = "00"></td><td id = "10"></td><td id = "20"></td><td id = "30">
</td><td id = "40"></td><td id = "50"></td><td id = "60"></td>
</tr>
<tr>
<td id = "01"></td><td id = "11"></td><td id = "21"></td><td id = "31">
</td><td id = "41"></td><td id = "51"></td><td id = "61"></td>
</tr>
<tr>
<td id = "02"></td><td id = "12" class="hit" ></td><td id = "22"></td><td id = "32">
</td><td id = "42"></td><td id = "52"></td><td id = "62"></td>
</tr>
<tr>
<td id = "03"></td><td id = "13" class="hit"></td><td id = "23"></td><td id = "33">
</td><td id = "43"></td><td id = "53"></td><td id = "63"></td>
</tr>
<tr>
<td id = "04"></td><td id = "14"></td><td id = "24"></td><td id = "34">
</td><td id = "44"></td><td id = "54"></td><td id = "64"></td>
</tr>
<tr>
<td id = "05"></td><td id = "15"></td><td id = "25"></td><td id = "35">
</td><td id = "45"></td><td id = "55"></td><td id = "65"></td>
</tr>
<tr>
<td id = "06"></td><td id = "16"></td><td id = "26"></td><td id = "36">
</td><td id = "46"></td><td id = "56"></td><td id = "66"></td>
</tr>
</table>

<form align="center" >
<input type ="text" id = "guess" placeholder="A0" >
<input type ="button" id ="firebutton" value="fire">
</form>
</div>
　<form action="upload.php" method="post" enctype="multipart/form-data" style ="margin-left: 400px;
	margin-right: 400px;" align = "center">
  <input type="file" name="file" id="file" />
　<input type="submit" name="submit" value="上傳檔案"  />
　</form>
<?php
echo "<table border='1' align='center'>";
        
        for($i=1;$i<=9;$i++){
            echo "<tr>";
            for($j=2;$j<=9;$j++){
                echo "<td> $j X $i=".$j*$i."</td>";
            }
            echo "</tr>";
        }    
echo "</table>";
?>

<?php
echo "<table border='1'><td>";
     for($i=1;$i<=9;$i++){
        echo"$i";
        $check = $i%3;
        if(!$check){
            echo"<br>";
        }
     }
echo "</td>";
?>
<?php
echo "<table border='1'><td>";
     for($i=1;$i<=9;$i++){
        echo"*";
        $check = $i%3;
        if(!$check){
            echo"<br>";
        }
     }
echo "</td>";
?>
</body>
</html>