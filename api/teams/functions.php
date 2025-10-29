<?php
require '../../config/database.php';

// def
function error422($message){
    $data = [
        'status' => 422,
        'message' => $message,
    ];
    header("HTTP/1.0 422 Unprocessable Entity"); 
    echo json_encode($data);
    exit();
}


// create
function storeTeam($teamInput) {
    global $conn;

    $team = mysqli_real_escape_string($conn, $teamInput['team']);

    // random id
    do {
        $team_id = rand(100000, 999999);
        $checkQuery = "SELECT team_id FROM teams WHERE team_id = '$team_id'";
        $checkResult = mysqli_query($conn, $checkQuery);
    } while (mysqli_num_rows($checkResult) > 0);

    $query = "INSERT INTO teams (team_id, team)
              VALUES ('$team_id', '$team')";

    $result = mysqli_query($conn, $query);

    if($result){
        $data = [
            'status' => 201,
            'message' => 'Team created successfully',
            'team_id' => $team_id
        ];
        header("HTTP/1.0 201 Created");
        return json_encode($data);
    } else {
        $data = [
            'status' => 500,
            'message' => 'Internal Server Error',
        ];
        header("HTTP/1.0 500 Internal Server Error");
        return json_encode($data);
    }
}

//read
function getTeamsList() {
    global $conn;

    $query = "SELECT team_id, team, created_at FROM teams ORDER BY created_at ASC";
    $query_run = mysqli_query($conn, $query);

    if($query_run){
        if(mysqli_num_rows($query_run) > 0){
             $res = mysqli_fetch_all($query_run, MYSQLI_ASSOC);

             $data = [
                'status' => 200,
                'message' => 'teams List Fetched Successfully',
                'data' => $res
            ];
            header("HTTP/1.0 200 OK");
            return json_encode($data);

        }else {
            $data = [
                'status' => 404,
                'message' => 'No Teams Found',
            ];
            header("HTTP/1.0 404 Not Found");
            return json_encode($data);
        }
    }else{
         $data = [
            'status' => 500,
            'message' => 'Internal Server Error',
        ];
        header("HTTP/1.0 500 Internal Server Error");
        return json_encode($data);
    }
}

// update
function updateTeam($teamInput){
    global $conn;

    $team_id = mysqli_real_escape_string($conn, $teamInput['team_id']);
    $team = mysqli_real_escape_string($conn, $teamInput['team']);

    // Validation
    if(empty(trim($team_id))){
        return error422('Enter team ID');
    }elseif(empty(trim($team))){
        return error422('Enter team name');
    }else {
        // Check
        $checkQuery = "SELECT * FROM teams WHERE team_id='$team_id'";
        $checkResult = mysqli_query($conn, $checkQuery);

        if(mysqli_num_rows($checkResult) == 0){
            $data = [
                'status' => 404,
                'message' => 'Team not found',
            ];
            header("HTTP/1.0 404 Not Found");
            return json_encode($data);
        }

        $query = "UPDATE teams SET
                  team='$team'
                  WHERE team_id='$team_id'";
        $result = mysqli_query($conn, $query);

        if($result){
            $data = [
                'status' => 200,
                'message' => 'Team Updated Successfully',
            ];
            header("HTTP/1.0 200 OK");
            return json_encode($data);
        }else{
            $data = [
                'status' => 500,
                'message' => 'Internal Server Error',
            ];
            header("HTTP/1.0 500 Internal Server Error");
            return json_encode($data);
        }
    }
}

// delete
function deleteTeam($teamInput){
    global $conn;

    if(empty($teamInput['team_id'])){
        return error422('Team ID is required');
    }

    $teamId = $teamInput['team_id'];

    // Check if team exists
    $checkStmt = $conn->prepare("SELECT team_id FROM teams WHERE team_id = ?");
    $checkStmt->bind_param("i", $teamId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if($checkResult->num_rows == 0){
        $data = [
            'status' => 404,
            'message' => 'Team Not Found',
        ];
        header("HTTP/1.0 404 Not Found");
        return json_encode($data);
    }

    // Delete the team
    $stmt = $conn->prepare("DELETE FROM teams WHERE team_id = ?");
    $stmt->bind_param("i", $teamId);
    $result = $stmt->execute();

    if($result){
        $data = [
            'status' => 200,
            'message' => 'Team Deleted Successfully',
        ];
        header("HTTP/1.0 200 OK");
        return json_encode($data);
    }else{
        $data = [
            'status' => 500,
            'message' => 'Internal Server Error',
        ];
        header("HTTP/1.0 500 Internal Server Error");
        return json_encode($data);
    }
}
?>