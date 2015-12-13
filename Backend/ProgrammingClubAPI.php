<?php

require_once 'API.class.php';

class Point {
	public $x;
	public $y;
	public $z;
}

class ProgrammingClubAPI extends API
{
	

	// The database
	private $mysqli = NULL;

	public function __construct($request, $origin) {
		$this->initDB();
		parent::__construct($request);
	}

	// Initializes and returns a mysqli object that represents our mysql database
	private function initDB() {
		$config = include("config.php");
		$this->mysqli = new mysqli($config['hostname'], 
			$config['usernane'], 
			$config['password'], 
			$config['databaseName']);
		
		if (mysqli_connect_errno()) { 
			echo "<br><br>There seems to be a problem with our database. Reload the page or try again later.";
			exit(); 
		}
	}

	private function select($sql) {
		$res = mysqli_query($this->mysqli, $sql);
		return mysqli_fetch_array($res, MYSQLI_ASSOC);
	}

	private function selectMultiple($sql) {
		$res = mysqli_query($this->mysqli, $sql);
		$finalArray = array();

		while($temp = mysqli_fetch_array($res, MYSQLI_ASSOC)) {
			array_push($finalArray, $temp);
		}

		return $finalArray;
	}

	private function insert($sql) {
		mysqli_query($this->mysqli, $sql);
	}

	private function update($sql) {
		mysqli_query($this->mysqli, $sql);
	}

	private function getDistance($point1, $point2) {
		return sqrt(pow($point1->x - $point2->x, 2) + pow($point1->y - $point2->y, 2) + pow($point1->z - $point2->z, 2));
	}

	private function loadProblems() {
		$problems = array();
		for($a = 0; $a < 20; $a++) {

			$points = array();
			$lines = preg_split('/\n|\r\n?/', file_get_contents("Input/".($a+1).".txt"));
			for($b = 0; $b < count($lines); $b++) {
				$components = explode(" ", trim($lines[$b]));
				
				$point = new Point();
				$point->x = intval($components[0]);
				$point->y = intval($components[1]);
				$point->z = intval($components[2]);
				
				array_push($points, $point);
			}
			array_push($problems, $points);
		}
		if(count($problems) != 20 || count($problems[0]) != 200) {
			echo "<br>";
			echo count($problems);
			echo count($problems[0]);
			echo "<br>";
			throw new Exception("Wrong Length of  problems array");
		}
 		return $problems;
	}
	private function containsAllIndexes($pointIndexes) {
		$used = array();
		for($b = 0; $b < 200; $b++) array_push($used, false);
		for($b = 0; $b < count($pointIndexes); $b++) $used[$pointIndexes[$b]] = true;
		for($b = 0; $b < count($used); $b++) {
			if($used[$b] == false) {
				echo "bbbb: ".$b." bbbb";
				return false;
			}
		}
		return true;
	}
	private function getDistanceFromOutputFile($outputFileContents) {
		$totalDistance = 0;
		$problems = $this->loadProblems();
		$outputFileContents = str_replace('\r\n', '\n', $outputFileContents);
		$lines = explode('\n', $outputFileContents);
		for($a = 0; $a < count($lines); $a++) {
			if($a == 0 || $a > 20) continue;

			$problemIndex = $a - 1;
			$pointIndexes = explode(" ", trim($lines[$a]));
			if(count($pointIndexes) != 200) {
				var_dump($pointIndexes);
				echo $problemIndex."<br>";
				echo count($pointIndexes);
				throw new Exception("Wrong number of point indexes", 1);
			}

			for($b = 0; $b < count($pointIndexes); $b++) $pointIndexes[$b] = intval($pointIndexes[$b]);

			if($this->containsAllIndexes($pointIndexes) == false) {
				throw new Exception("Not using all indexes", 1);
			}			

			for($b = 0; $b < count($pointIndexes); $b++) {
				if($b == count($pointIndexes)-1) continue;
				$point1 = $problems[$problemIndex][$pointIndexes[$b]];
				$point2 = $problems[$problemIndex][$pointIndexes[$b+1]];
				$totalDistance += $this->getDistance($point1, $point2);
			}
			$totalDistance += $this->getDistance($problems[$problemIndex][$pointIndexes[count($pointIndexes)-1]], $problems[$problemIndex][$pointIndexes[0]]);
		}
		if($totalDistance == 0 || count($lines) < 21 || count($lines) > 22) {
			echo $totalDistance."<br>";
			echo count($lines);
			throw new Exception("Wrong number of lines or total distance is 0", 1);
		}
		return $totalDistance;
	}

	// API ENDPOINTS
	protected function submission() {
		if(isset($_GET['getAll'])) {
			return $this->selectMultiple("SELECT * FROM Submission ORDER BY distance ASC");
		} else if(isset($_POST['name']) && isset($_FILES['outputFile']['name'])) {
			$name = "";
			$distance = 0;
			try {
				$name = $this->mysqli->real_escape_string($_POST['name']);
				$distance = $this->getDistanceFromOutputFile($this->mysqli->real_escape_string(file_get_contents($_FILES['outputFile']["tmp_name"])));
			} catch(Exception $e) {
				echo $e->getMessage();
				echo "This echo tells the js there is an error.";
				return "ERROR";
			}
			
			// Update the distance if the name is already and if the distance is smaller than the current one
			$nameArray = $this->select("SELECT * FROM Submission WHERE name = '$name'");
			if($nameArray['name'] != NULL) {
				if($nameArray['distance'] > $distance) {
					$this->insert("UPDATE Submission SET distance = $distance WHERE name = '$name'");
				}
			} else {
				$this->insert("INSERT INTO Submission (name, distance) VALUES ('$name', $distance)");
			}

			return intval($distance)."";

			// Store file on our server
			$targetPath = "Output/";
			$ext = explode('.', basename( $_FILES['outputFile']['name']));
			$targetPath = $targetPath . md5(uniqid()) . "." . $ext[count($ext)-1];
			move_uploaded_file($_FILES['outputFile']['tmp_name'], $targetPath);

			return "Success";
		} else {
			return "ERROR";
		}
	}

 }

 ?>