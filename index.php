<?php

function htf($xx, $h) {
    $f = "PT" . $xx[$h] . "H";
    if (sizeof($xx) > $h && strlen($xx[$h+1]) > 0) {
        $f .= $xx[$h+1] . "M";
    }
    return new DateInterval($f);
}

$heures = "";
$taux = isset($_POST['taux']) ? floatval(str_replace(',', '.', $_POST['taux'])) : 3.9;
$debug = "";
$detail = "";

function pre($v) {
    global $debug;
    if (isset($_GET['d'])) {
        $debug .= "<pre class=\"d\">";
        ob_start();
        var_dump($v);
        $debug.=ob_get_clean()."</pre>";
    }
}

function d($v) {
    global $detail;
    $detail .= "    " . $v . "\n";
}

function toh($t) {
    return $t->m * 30 * 24 + $t->d * 24 + $t->h;
}

function f($t, $f=false) {
    $hours = toh($t);
    $minutes = $t->i;
    $r = sprintf("<span class=\"r\">%02dh%02dm</span>", $hours, $minutes);
    if ($f) {
        $r .= sprintf(" (soit <span class=\"r\">%.02f</span> heures)", round($hours + $minutes / 60.0, 2, PHP_ROUND_HALF_DOWN));
    }
    return $r;
}

if (isset($_POST['heures'])) {
    pre($_POST['heures']);
    $heures = $_POST['heures'];
    $h = "([0-9]+)(?:\s*[Hh]([0-9]*))";
    if (preg_match_all("/(?:(?P<interval>$h\s*-\s*$h)|(?P<hours>$h))/", $_POST['heures'], $matches, PREG_SET_ORDER)) {
        $ref = new DateTime();
        $res = clone $ref;
        $start = $end = null;
        $add = false;
        foreach($matches as $match) {
            pre($match);
            if (isset($match['interval']) && !empty($match['interval'])) {
                $curref = clone $res;
                $start = htf($match, 2);
                $end = htf($match, 4);
                $res->add($end);
                $res->sub($start);
                $v = $curref->diff($res);
                $t = sprintf("<span class=\"i\">intervalle %s -> %s</span>", f($start), f($end));
                $end = $start = null;
                $add = true;
            } elseif (isset($match['hours']) && !empty($match['hours'])) {
                $hours = htf($match, 7);
                $res->add($hours);
                $v = $hours;
                $t = "<span class=\"h\">nombre d'heures</span>";
                $add = true;
            }

            if ($add) {
                $x = $ref->diff($res);
                d(sprintf("%s = %s (total: %s)", $t, f($v, true), f($x, true)));
                $add = false;
            }
        }
        $delta = $ref->diff($res);
        $hours = $delta->m * 30 * 24 + $delta->d * 24 + $delta->h;
        $minutes = $delta->i;
        $hoursfloat = round($hours + $minutes / 60.0, 2, PHP_ROUND_HALF_DOWN);
        $minutes = str_pad((string)$minutes, 2, "0", STR_PAD_RIGHT);
        $salaire = round($hoursfloat * floatval($taux), 2, PHP_ROUND_HALF_DOWN);
    }
    $id = isset($_GET['k']) && !empty($_GET['k']) ? intval($_GET['k']) : 0;
    $archive = file_get_contents('./db.json');
    if ($archive !== FALSE) {
        $arch = json_decode($archive);
    } else {
        $arch = [];
    }
    if (!isset($arch[$id])) {
        $arch[$id] = array();
    }
    $arch[$id][] = array("t" => new DateTime(), "d" => $_POST['heures'], "x" => $_POST['taux']);
    $json = json_encode($arch);
    if ($json !== FALSE) {
        file_put_contents('./db.json', $json);
    }
}
?><!DOCTYPE html>
<html>
<head>
<title>Calculateur d'heures</title>
<style>
body { font-family: Helvetica Neue,Helvetica,Arial,sans-serif; font-size: 14px; line-height: 1.4; display: flex; align-items: center; height: 100%; justify-content: center; }
body > div { display: flex; flex-direction: column; align-items: center; justify-content: center; }
pre { font-style: italic; }
legend { font-family: monospace; }
div { display: flex; align-items: center; justify-content: center; }
textarea { flex-grow: 1 }
#t { margin: 8px; margin-left: 0px; font-weight: bold; }
#t input { margin: 8px; padding: 4px; }
input[type=submit] { width: 33vw; padding: 8px; text-transform: uppercase; background-color: #f50057; color: white; font-weight: bold; border-radius: 8px; box-shadow: rgba(0, 0, 0, 0.117647) 0px 1px 6px, rgba(0, 0, 0, 0.117647) 0px 1px 4px; text-decoration: none; outline: none; } 
.d { font-style: normal; }
.r { color: red; font-style: normal; }
.i { color: blue; font-style: normal; }
.h { color: orange; font-style: normal; }
#d { background-color: #e8e8e8; font-size: 0.9em; border-style: solid; border-width: 1px; margin-top: 64px; }
#d legend { border-style: solid; border-width: 1px; background-color: #e8e8e8; border-color: threedface; padding: 4px; }
.x { font-weight: bold; border-color: #f50057; border-width: 8px; background-color: #68efad; margin-top: 32px; border-radius: 16px; border-style: outset; min-width: 500px; }
.x legend {  background-color: #68efad; border: solid 2px #f50057; padding: 4px; }
.x > div { display: flex; font-size: 1.5em; }
.x > div:first-of-type label { text-transform: uppercase; }
.x > div label { text-align: right; padding-right: 12px; width: 50%; }
.x > div span { display: block; text-align: left; padding-left: 12px; width: 50%; }
</style>
</head>
<body>
<div>
<h1>Calculateur d'heures</h1>
<form method="post">
  <fieldset>
    <legend>Entrer les heures ici</legend>
    <div><textarea name="heures" placeholder="Entrer les heures ici (intervalles &quot;8h15 - 16h&quot; ou nombres d'heures &quot;9h45&quot;)" rows="10"><?php echo $heures; ?></textarea></div>
    <div id="t"><label for="taux">Taux horaire</label><input type="text" name="taux" value="<?php echo $taux; ?>" size="4"><span>€/h</span></div>
    <div><input value="Calculer !" type="submit"></div>
  </fieldset>
</form>
<?php if (isset($salaire)) { ?><fieldset class="x"><legend>Résultat</legend><div><label>Total</label><span><?php echo $hours."h".$minutes."m"; ?></span></div><hr /><div><label>À déclarer</label><span><?php echo sprintf("%.02f", $hoursfloat)." heures"; ?></span></div><div><label>Salaire</label><span><?php echo "$salaire €"; ?></span></div></fieldset><?php } ?>
<?php if (!empty($detail)) echo "<fieldset id=\"d\"><legend>Détails</legend><pre>$detail</pre></fieldset>"; ?>
<?php if (!isset($_GET['debug'])) echo $debug; ?>
</div>
</body>
</html>

