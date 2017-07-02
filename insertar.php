<?php

  /*Obtenemos mediante metodo POST la matricula o registro*/
    $registro=$_POST["registro"];

  // $registroConCero=$_POST["registro"];
  // $registro = substr($registroConCero, 1);

  /*Conectamos a la base*/
  include('conexion.php');
  /*Verificamos si existe la matricula*/
  $sql="SELECT nom_asis, ap_paterno, ap_materno FROM asistente WHERE registro='".$registro."'";
  $result=$conn->query($sql);

  if(mysqli_num_rows($result) > 0)
  {
    /*Si la matricula existe obtenemos su nombre*/
    while($row = mysqli_fetch_array($result))
      {
        $nombre=$row["nom_asis"];
        $paterno=$row["ap_paterno"];
        $materno=$row["ap_materno"];
      }
      //#
      $nombre_completo = $nombre." ".$paterno." ".$materno."<br>";

      /*Obtenemos la hora de ENTRADA esta puede ser 0, pd: en la base especificamos 0 por DEFAULT*/
      $sqlEntrada = "SELECT entrada FROM asistente WHERE registro='".$registro."'";
      $resultEntrada=$conn->query($sqlEntrada);
      while($rowEntrada = mysqli_fetch_array($resultEntrada))
      {
        $entrada=$rowEntrada["entrada"];
      }
      if($entrada==''){
        $entrada='0:0';
      }

      /*Si la hora esta vacia*/
      if($entrada==''||$entrada=='0:0'){
        echo "Alumno: $nombre_completo";
        echo "Estado: ENTRANDO"."<br>";
        /*Conseguimos la hora*/
        $hora = getCurrentHora();
        echo "Hra Entrada: $hora";
        /*Asignamos la hora de ENTRADA*/
        $sqlRegistroSalida="UPDATE asistente SET entrada='".$hora."' WHERE registro='".$registro."'";
        $result=$conn->query($sqlRegistroSalida);
      }
      /*Sino esta vacia*/
      else {
        /*Osea ya paso un tiempo*/
        /*Conseguimos la hora ACTUAL en la que esta SALIENDO*/
        echo "Alumno: $nombre_completo";
        echo "Estado: SALIENDO"."<br>"."<br>";
        $horaSalida = getCurrentHora();


        /*Obtenemos la hora de ENTRADA del usuario*/
        $sqlEntrada = "SELECT entrada FROM asistente WHERE registro='".$registro."'";
        $resultEntrada=$conn->query($sqlEntrada);
        while($rowEntrada = mysqli_fetch_array($resultEntrada))
        {
          $horaEntrada=$rowEntrada["entrada"];
        }
        echo "Hra Entrada fue: $horaEntrada"."<br>";
        echo "Hra Salida: $horaSalida"."<br>"."<br>";
        /*Obtenemos la DIFERENCIA de horas entr la hora de ENTRADA y la CURRENT HORA*/
        $difHrs=diferenciaHoras($horaEntrada, $horaSalida);
        echo "Tiempo Acumulado: $difHrs"."<br>";

        /*Ahora esta diferenciaHoras se lo vamos a sumar al tiempo acumulado*/

        /*Conseguimos tiempo ACUMULADO*/
        $sqlTiempoAcumulad="SELECT total FROM asistente WHERE registro='".$registro."'";
        $resultTiempoAcumulado=$conn->query($sqlTiempoAcumulad);
        while($rowTiempoAcumulado = mysqli_fetch_array($resultTiempoAcumulado))
          {
              $tiempoAcumulado=$rowTiempoAcumulado["total"];
          }
          // echo "tiempoAcumulado: $tiempoAcumulado"."<br>";
          if($tiempoAcumulado==''){
              $tiempoAcumulado="0:0";
          }
          echo "Tiempo Pasado: $tiempoAcumulado"."<br>";

        /*Sumamos los tiempos*/
        $sumaTotalTiempo=sumarHoras($tiempoAcumulado, $difHrs);
        echo "Total Tiempo Acumulado: $sumaTotalTiempo"."<br>";
        /*Insertamos el nuevo tiempo ACUMULADO*/
        $sqlNuevoTiempoAcumulado="UPDATE asistente SET total='".$sumaTotalTiempo."' WHERE registro='".$registro."'";
        $result=$conn->query($sqlNuevoTiempoAcumulado);

        /*Reseteamos la ENTRADA a 0:0*/
        $sqlBanderaEntradaReset="UPDATE asistente SET entrada='0:0' WHERE registro='".$registro."'";
        $result=$conn->query($sqlBanderaEntradaReset);
      }

  } else {
    echo "ERROR no se encuentra en la base";
  }

  $conn->close();





  function sumarHoras($acumuladoTime, $nuevoTime){
    /*
    Se esperan parametros asi:
    $acumuladoTime="2:45";
    $nuevoTime="4:36";*/
    // echo "Hora acumulada: $acumuladoTime"."<br>";
    // echo "Nuevo tiempo acumulado: $nuevoTime"."<br>";

    /*Tiempo acumulado*/
    $myArrayAcumuladoTime=explode(":", $acumuladoTime);

    $hrsAcumuladoTime=$myArrayAcumuladoTime[0];
    $minsAcumuladoTime=$myArrayAcumuladoTime[1];

    /*Nuevo Time*/
    $myArrayNewTime=explode(":", $nuevoTime);

    $hraNewTime=$myArrayNewTime[0];
    $minNewTime=$myArrayNewTime[1];

    /*Calculo*/
    $sumHrs=$hrsAcumuladoTime+$hraNewTime;
    $sumMins=$minsAcumuladoTime+$minNewTime;

    /*Si se pasan los MINUTOS*/
    if($sumMins>59){
      /*Quitamos hora para dejarlo en minutos y se la sumamos a la de horas*/
      $sumMins-=60;
      $sumHrs+=1;
    }

    // echo "Total hrs agregadas: $sumHrs:$sumMins"."<br>";
    return "$sumHrs:$sumMins";
  }

  function getCurrentHora(){
    $tz = 'America/Mexico_City';
    $time = time();
    $dt = new DateTime("now", new DateTimeZone($tz)); //first argument "must" be a string
    $dt->setTimestamp($time); //adjust the object to correct timestamp
    // echo $dt->format('d.m.Y,H:i:s')."<br>";
    // return $dt->format('d.m.Y,H:i');
    return $dt->format('H:i');
  }

  function diferenciaHoras($entrada, $salida){
      /*Ingresa dos cadenas como las siguientes:
      $entrada="10.01.2017,6:00";
      $salida="10.01.2017,12:00";*/

      // echo "entrda: $entrada"."<br>";
      // echo "salida: $salida"."<br>";

      $tiempoArrayIn = explode(":", $entrada);
      $hraIn=$tiempoArrayIn[0];
      $minIn=$tiempoArrayIn[1];

      // echo "$hraIn"."<br>";
      // echo "$minIn"."<br>";

      $tiempoArrayOut = explode(":", $salida);
      $hraOut=$tiempoArrayOut[0];
      $minOut=$tiempoArrayOut[1];

      // echo "$hraOut"."<br>";
      // echo "$minOut"."<br>";

      // /*Solo calcula el TOTAL horas no involucra fechas*/
      // /*Es decir calculara el TOTAL horas del mismo dia*/
      //
      // /*Separamos la fecha de las horas para ENTRADA*/
      // $myArrayEntrada = explode(",", $entrada);
      // $tiempoIn=$myArrayEntrada[1];
      // // echo "$tiempoIn"."<br>";
      //
      // /*Separamos las HORAS de los MINUTOS para ENTRADA*/
      // $tiempoArrayIn = explode(":", $tiempoIn);
      // $hraIn=$tiempoArrayIn[0];
      // $minIn=$tiempoArrayIn[1];
      // // echo "$hraIn"."<br>";
      // // echo "$minIn"."<br>";
      //
      // /*Separamos la fecha de las horas para SALIDA*/
      // $myArraySalida = explode(",", $salida);
      // $tiempoOut=$myArraySalida[1];
      // // echo "$tiempoOut"."<br>";
      //
      // /*Separamos las HORAS de los MINUTOS para SALIDA*/
      // $tiempoArrayOut = explode(":", $tiempoOut);
      // $hraOut=$tiempoArrayOut[0];
      // $minOut=$tiempoArrayOut[1];
      // // echo "$hraOut"."<br>";
      // // echo "$minOut"."<br>";
      // // echo "<br>";

      /*Obtenemos Diferencia Horas*/
      $resHrs=$hraOut-$hraIn;
      // echo "Dif hrs: $resHrs"."<br>";

      /*Obtenemos Diferencia Minutos*/
      $resMins=$minOut-$minIn;
      // echo "Dif mins: $resMins"."<br>";

      /*Siguiendo el algoritmo:
          1.-Resta las horas.
          2.-Resta los minutos.
          3.-Si los minutos son negativos, suma 60 a los minutos y resta 1 de las horas.

          Si la diferencia de minutos es menor a cero o negativa, restamos 1 hra y sumamos 60s.
      */
      if($resMins<0){
        $resHrs-=1;
        //echo "New Dif Hrs: $resHrs"."<br>";
        $resMins+=60;
        // echo "New Dif mins: $resMins"."<br>";

        /*Al sumar 60mins por lo regular lo podemos convertir en horas
          agregamos las hrs que estan implicitas.
        */
        if($resMins>60){
          /*Divicion entera para obtener el numero de horas extra*/
          $addHrs=intdiv($resHrs, 60);
          /*Se las agregamos a las horas que teniamos*/
          $resHrs+=$addHrs;
          /*Los minutos les dejamos el residuo*/
          $resMins=$resMins%60;

          // echo "fin hrs: $resHrs"."<br>";
          // echo "fin mins: $resMins"."<br>";
        }
      }
      // echo "$resHrs:$resMins"."<br>";
      return "$resHrs:$resMins";
  }
?>
