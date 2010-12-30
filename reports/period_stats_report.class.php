<?php

// CALCULATE PERIOD STATS Reports --
// Status & Issue classes

include_once "period_stats.class.php";
//require_once "../Artichow/LinePlot.class.php";
//require_once "../Artichow/Graph.class.php";

class PeriodStatsReport {
  var $start_year;
  var $start_day;
  var $periodStatsList;
  
  var $teamid;

  public function PeriodStatsReport($start_year, $teamid) {
    $this->start_year = $start_year;
    $this->start_day = 1;
    $this->periodStatsList = array();
    
    $this->teamid = $teamid;
  }

  // Compute monthly reports for the complete year
  public function computeReport() {
  	
    global $periodStatsExcludedProjectList;
  	
    $now = time();

    for ($start_month=1; $start_month<13; $start_month++) {
      $startTimestamp = mktime(0, 0, 1, $start_month, $this->start_day, $this->start_year);
      $endTimestamp   = mktime(0, 0, 1, ($start_month + 1), $this->start_day, $this->start_year);

      if ($startTimestamp > $now) { break; }

      $periodStats = new PeriodStats($startTimestamp, $endTimestamp);
      
      // only projects for specified team, except excluded projects
      $projectList = array();
      $query = "SELECT project_id ".
               "FROM `codev_team_project_table` ".
               "WHERE team_id = $this->teamid";
      $result = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result)) {
      	
        // remove FDL project
      	if (! in_array($row->project_id, $periodStatsExcludedProjectList))  {
      		$projectList[] = $row->project_id; 
      	}
      }
      
      $periodStats->projectList = $projectList;
      
      $periodStats->computeStats();

      $this->periodStatsList[$startTimestamp] = $periodStats;
    }
  }

  function displayHTMLReport() {
    global $status_new;
    global $status_feedback;
    global $status_ack;
    global $status_analyzed;
    global $status_accepted;
    global $status_openned;
    global $status_resolved;
    global $status_delivered;
    global $status_closed;
  	
    $nb_resolved = 0;
    $nb_delivered = 0;
    $nb_closed = 0;

    echo "<table>\n";
    echo "<caption title='Bilan mensuel SAUF SuiviOp.'>Bilan mensuel (nbre de fiches / status &agrave; la fin du mois)</caption>";
    echo "<tr>\n";
    echo "<th>Date</th>\n";
    echo "<th title='Nbre de fiches cr&eacute;&eacute;es SAUF SuiviOp, FDL'>Nb submissions</th>\n";
    echo "<th>New</th>\n";
    echo "<th>Acknowledge</th>\n";
    echo "<th>Feedback</th>\n";
    echo "<th>Analyzed</th>\n";
    echo "<th>Accepted</th>\n";
    echo "<th>Openned</th>\n";
    echo "<th>Resolved</th>\n";
    echo "<th>Delivered</th>\n";
    echo "<th>Closed</th>\n";
    echo "<th title='Nbre de fiches r&eacute;solues SAUF SuiviOp'>Nb resolutions</th>\n";
    echo "</tr>\n";

    foreach ($this->periodStatsList as $date => $ps) {
      // Calc diff vs/ previous period
      $nb_resolved = $ps->statusCountList[$status_resolved] - $nb_resolved;
      $nb_delivered = $ps->statusCountList[$status_delivered] - $nb_delivered;
      $nb_closed = $ps->statusCountList[$status_closed] - $nb_closed;
      // Nb resolutions
      $nb_resolutions = $nb_resolved + $nb_delivered + $nb_closed;

      // Disp
      $tableLine = "<tr>\n";
      $tableLine .= "<td class=\"right\">".date("F Y", $date)."</td>\n";
      $tableLine .= "<td class=\"right\">".$ps->statusCountList["submitted"]."</td>\n";
      $tableLine .= "<td class=\"right\">".$ps->statusCountList[$status_new]."</td>\n";
      $tableLine .= "<td class=\"right\">".$ps->statusCountList[$status_ack]."</td>\n";
      $tableLine .= "<td class=\"right\">".$ps->statusCountList[$status_feedback]."</td>\n";
      $tableLine .= "<td class=\"right\">".$ps->statusCountList[$status_analyzed]."</td>\n";
      $tableLine .= "<td class=\"right\">".$ps->statusCountList[$status_accepted]."</td>\n";
      $tableLine .= "<td class=\"right\">".$ps->statusCountList[$status_openned]."</td>\n";
      $tableLine .= "<td class=\"right\">".$ps->statusCountList[$status_resolved]."</td>\n";
      $tableLine .= "<td class=\"right\">".$ps->statusCountList[$status_delivered]."</td>\n";
      $tableLine .= "<td class=\"right\">".$ps->statusCountList[$status_closed]."</td>\n";
      $tableLine .= "<td class=\"right\">".$nb_resolutions."</td>\n";
      $tableLine .= "</tr>\n";
      echo "$tableLine";
      
      // Reinit for next period
      $nb_resolved = $ps->statusCountList[$status_resolved];
      $nb_delivered = $ps->statusCountList[$status_delivered];
      $nb_closed = $ps->statusCountList[$status_closed];
    }
    echo "</table>\n";
  }

} // end class PeriodStatsReport

?>


