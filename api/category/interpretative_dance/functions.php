<?php
require '../../../config/database.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

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

// store interpretative score
function storeInterpretativeScore($scoreInput){
    global $conn;

    if (!isset($_SESSION['user_id'])) {
        return error422('User not logged in');
    }

    if (!isset($scoreInput['team_id']) || empty(trim($scoreInput['team_id']))) {
        return error422('Enter team ID');
    }
    if (!isset($scoreInput['originality']) || empty(trim($scoreInput['originality']))) {
        return error422('Enter originality score');
    }
    if (!isset($scoreInput['mastery_of_steps']) || empty(trim($scoreInput['mastery_of_steps']))) {
        return error422('Enter mastery of steps score');
    }
    if (!isset($scoreInput['choreography_and_style']) || empty(trim($scoreInput['choreography_and_style']))) {
        return error422('Enter choreography and style score');
    }
    if (!isset($scoreInput['costume_and_props']) || empty(trim($scoreInput['costume_and_props']))) {
        return error422('Enter costume and props score');
    }
    if (!isset($scoreInput['stage_presence']) || empty(trim($scoreInput['stage_presence']))) {
        return error422('Enter stage presence score');
    }

    $team_id = mysqli_real_escape_string($conn, $scoreInput['team_id']);
    $judge_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);
    $originality = mysqli_real_escape_string($conn, $scoreInput['originality']);
    $mastery_of_steps = mysqli_real_escape_string($conn, $scoreInput['mastery_of_steps']);
    $choreography_and_style = mysqli_real_escape_string($conn, $scoreInput['choreography_and_style']);
    $costume_and_props = mysqli_real_escape_string($conn, $scoreInput['costume_and_props']);
    $stage_presence = mysqli_real_escape_string($conn, $scoreInput['stage_presence']);
    $total_score = $originality + $mastery_of_steps + $choreography_and_style + $costume_and_props + $stage_presence;

    // Generate unique score_id
    do {
        $score_id = rand(100000, 999999);
        $checkQuery = "SELECT score_id FROM interpretative_score WHERE score_id = '$score_id'";
        $checkResult = mysqli_query($conn, $checkQuery);
    } while (mysqli_num_rows($checkResult) > 0);

    $query = "INSERT INTO interpretative_score (score_id, team_id, judge_id, originality, mastery_of_steps, choreography_and_style, costume_and_props, stage_presence, total_score)
              VALUES ('$score_id', '$team_id', '$judge_id', '$originality', '$mastery_of_steps', '$choreography_and_style', '$costume_and_props', '$stage_presence', '$total_score')";
    $result = mysqli_query($conn, $query);

    if($result){
        // Calculate average total_score from all judges submitted so far for this team
        $avg_query = "SELECT AVG(total_score) AS avg_total FROM interpretative_score WHERE team_id = '$team_id'";
        $avg_result = mysqli_query($conn, $avg_query);
        $avg_row = mysqli_fetch_assoc($avg_result);
        $avg_total = $avg_row['avg_total'];

        // The final score is the average total_score
        $percentage = $avg_total;

        // Check if interpretative_final_score row exists for this team_id
        $check_query = "SELECT team_id FROM interpretative_final_score WHERE team_id = '$team_id'";
        $check_result = mysqli_query($conn, $check_query);
        if (mysqli_num_rows($check_result) > 0) {
            // Update existing row
            $update_query = "UPDATE interpretative_final_score SET final_score = '$percentage', updated_at = NOW() WHERE team_id = '$team_id'";
            mysqli_query($conn, $update_query);
        } else {
            // Insert new row
            $insert_query = "INSERT INTO interpretative_final_score (team_id, final_score, created_at, updated_at) VALUES ('$team_id', '$percentage', NOW(), NOW())";
            mysqli_query($conn, $insert_query);
        }

        $data = [
            'status' => 201,
            'message' => 'Interpretative Score Created Successfully',
            'has_submitted' => isset($_SESSION['has_submitted']) ? (bool)$_SESSION['has_submitted'] : false
        ];
        header("HTTP/1.0 201 Created");
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

// mark as done *

// READ - Get All Interpretative Scores
function getAllInterpretativeScores($params = []){
    global $conn;

    $whereClause = "";
    if (isset($params['team']) && !empty(trim($params['team']))) {
        $team = mysqli_real_escape_string($conn, $params['team']);
        $whereClause = "WHERE t.team = '$team'";
    }

    $query = "SELECT
                vs.score_id,
                vs.team_id,
                vs.judge_id,
                vs.originality,
                vs.mastery_of_steps,
                vs.choreography_and_style,
                vs.costume_and_props,
                vs.stage_presence,
                vs.total_score,
                vs.created_at
              FROM interpretative_score vs
              INNER JOIN teams t ON vs.team_id = t.team_id
              $whereClause
              ORDER BY vs.judge_id, vs.total_score DESC";

    $result = mysqli_query($conn, $query);

    if($result){
        if(mysqli_num_rows($result) > 0){
            $res = mysqli_fetch_all($result, MYSQLI_ASSOC);

            // Group scores by judge_id
            $groupedScores = [];
            foreach ($res as $score) {
                $judge_id = $score['judge_id'];
                if (!isset($groupedScores['judge_' . $judge_id])) {
                    $groupedScores['judge_' . $judge_id] = [];
                }
                $groupedScores['judge_' . $judge_id][] = $score;
            }

            $data = [
                'status' => 200,
                'message' => 'Interpretative Scores for All Judges Fetched Successfully',
                'data' => $groupedScores
            ];
            header("HTTP/1.0 200 OK");
            return json_encode($data);
        }else{
            $data = [
                'status' => 404,
                'message' => 'No Interpretative Scores Found',
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

// READ - Get Interpretative Score by score_id
function getInterpretativeScores($scoreParams){
    global $conn;

    $score_id = mysqli_real_escape_string($conn, $scoreParams['score_id']);

    if(empty(trim($score_id))){
        return error422('Enter score ID');
    }

    $query = "SELECT
                vs.score_id,
                vs.team_id,
                vs.judge_id,
                vs.originality,
                vs.mastery_of_steps,
                vs.choreography_and_style,
                vs.costume_and_props,
                vs.stage_presence,
                vs.total_score,
                vs.created_at
              FROM interpretative_score vs
              INNER JOIN teams t ON vs.team_id = t.team_id
              WHERE vs.score_id = '$score_id' LIMIT 1";

    $result = mysqli_query($conn, $query);

    if($result){
        if(mysqli_num_rows($result) == 1){
            $res = mysqli_fetch_assoc($result);

            $data = [
                'status' => 200,
                'message' => 'Interpretative Score Fetched Successfully',
                'data' => $res
            ];
            header("HTTP/1.0 200 OK");
            return json_encode($data);
        }else{
            $data = [
                'status' => 404,
                'message' => 'No Interpretative Scores Found',
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

// READ - Get Interpretative Score by team_id
function getInterpretativeScoreByTeamId($scoreParams){
    global $conn;

    $team_id = mysqli_real_escape_string($conn, $scoreParams['team_id']);

    if(empty(trim($team_id))){
        return error422('Enter team ID');
    }

    $query = "SELECT
                vs.score_id,
                vs.team_id,
                vs.judge_id,
                vs.originality,
                vs.mastery_of_steps,
                vs.choreography_and_style,
                vs.costume_and_props,
                vs.stage_presence,
                vs.total_score,
                vs.created_at
              FROM interpretative_score vs
              INNER JOIN teams t ON vs.team_id = t.team_id
              WHERE vs.team_id = '$team_id'";

    $result = mysqli_query($conn, $query);

    if($result){
        if(mysqli_num_rows($result) > 0){
            $res = mysqli_fetch_all($result, MYSQLI_ASSOC);

            $data = [
                'status' => 200,
                'message' => 'Interpretative Scores Fetched Successfully',
                'data' => $res
            ];
            header("HTTP/1.0 200 OK");
            return json_encode($data);
        }else{
            $data = [
                'status' => 404,
                'message' => 'No Interpretative Score Found for this Team',
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

// READ - Get Interpretative Scores by Judge ID
function getInterpretativeScoresByJudge($judgeParams){
    global $conn;

    $judge_id = mysqli_real_escape_string($conn, $judgeParams['judge_id']);

    if(empty(trim($judge_id))){
        return error422('Enter judge ID');
    }

    $whereClause = "WHERE vs.judge_id = '$judge_id'";
    if (isset($judgeParams['team']) && !empty(trim($judgeParams['team']))) {
        $team = mysqli_real_escape_string($conn, $judgeParams['team']);
        $whereClause .= " AND t.team = '$team'";
    }

    $query = "SELECT
                vs.score_id,
                vs.team_id,
                vs.judge_id,
                vs.originality,
                vs.mastery_of_steps,
                vs.choreography_and_style,
                vs.costume_and_props,
                vs.stage_presence,
                vs.total_score,
                vs.created_at
              FROM interpretative_score vs
              INNER JOIN teams t ON vs.team_id = t.team_id
              $whereClause
              ORDER BY vs.created_at DESC";

    $result = mysqli_query($conn, $query);

    if($result){
        if(mysqli_num_rows($result) > 0){
            $res = mysqli_fetch_all($result, MYSQLI_ASSOC);

            $data = [
                'status' => 200,
                'message' => 'Interpretative Scores for Judge Fetched Successfully',
                'data' => $res
            ];
            header("HTTP/1.0 200 OK");
            return json_encode($data);
        }else{
            $data = [
                'status' => 404,
                'message' => 'No Interpretative Scores Found for this Judge',
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
?>