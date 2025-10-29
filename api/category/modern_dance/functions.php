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

// store vocal score
function storeModernScore($scoreInput){
    global $conn;

    if (!isset($_SESSION['user_id'])) {
        return error422('User not logged in');
    }

    if (!isset($scoreInput['cand_id']) || empty(trim($scoreInput['cand_id']))) {
        return error422('Enter candidate ID');
    }
    if (!isset($scoreInput['mastery_of_steps']) || empty(trim($scoreInput['mastery_of_steps']))) {
        return error422('Enter mastery of Steps score');
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
    if (!isset($scoreInput['audience_impact']) || empty(trim($scoreInput['audience_impact']))) {
        return error422('Enter audience impact score');
    }

    $cand_id = mysqli_real_escape_string($conn, $scoreInput['cand_id']);
    $judge_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);
    $mastery_of_steps = mysqli_real_escape_string($conn, $scoreInput['mastery_of_steps']);
    $choreography_and_style = mysqli_real_escape_string($conn, $scoreInput['choreography_and_style']);
    $costume_and_props = mysqli_real_escape_string($conn, $scoreInput['costume_and_props']);
    $stage_presence = mysqli_real_escape_string($conn, $scoreInput['stage_presence']);
    $audience_impact = mysqli_real_escape_string($conn, $scoreInput['audience_impact']);

    // mark as done*

    // Generate unique score_id
    do {
        $score_id = rand(100000, 999999);
        $checkQuery = "SELECT score_id FROM modern_score WHERE score_id = '$score_id'";
        $checkResult = mysqli_query($conn, $checkQuery);
    } while (mysqli_num_rows($checkResult) > 0);

    $total_score = $mastery_of_steps + $choreography_and_style + $costume_and_props + $stage_presence + $audience_impact;

    $query = "INSERT INTO modern_score (score_id, cand_id, judge_id, mastery_of_steps, choreography_and_style, costume_and_props, stage_presence, audience_impact, total_score)
              VALUES ('$score_id', '$cand_id', '$judge_id', '$mastery_of_steps', '$choreography_and_style', '$costume_and_props', '$stage_presence', '$audience_impact', '$total_score')";
    $result = mysqli_query($conn, $query);

    if($result){
        // Calculate average total_score from all judges submitted so far for this contestant
        $avg_query = "SELECT AVG(total_score) AS avg_total FROM modern_score WHERE cand_id = '$cand_id'";
        $avg_result = mysqli_query($conn, $avg_query);
        $avg_row = mysqli_fetch_assoc($avg_result);
        $avg_total = $avg_row['avg_total'];

        // The final score is the average total_score (sum of all judges' total_scores divided by number of judges)
        $percentage = $avg_total;

        // Check if modern_final_score row exists for this cand_id
        $check_query = "SELECT cand_id FROM modern_final_score WHERE cand_id = '$cand_id'";
        $check_result = mysqli_query($conn, $check_query);
        if (mysqli_num_rows($check_result) > 0) {
            // Update existing row
            $update_query = "UPDATE modern_final_score SET final_score = '$percentage' WHERE cand_id = '$cand_id'";
            mysqli_query($conn, $update_query);
        } else {
            // Insert new row
            $insert_query = "INSERT INTO modern_final_score (cand_id, final_score) VALUES ('$cand_id', '$percentage')";
            mysqli_query($conn, $insert_query);
        }

        $data = [
            'status' => 201,
            'message' => 'Modern Score Created Successfully',
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

// READ - Get All Vocal Scores
function getAllModernScores($params = []){
    global $conn;

    $whereClause = "";
    if (isset($params['team']) && !empty(trim($params['team']))) {
        $team = mysqli_real_escape_string($conn, $params['team']);
        $whereClause = "WHERE vc.team = '$team'";
    }

    $query = "SELECT
                vs.score_id,
                vs.cand_id as team_id,
                vc.team,
                vs.judge_id,
                vs.mastery_of_steps,
                vs.choreography_and_style,
                vs.costume_and_props,
                vs.stage_presence,
                vs.audience_impact,
                vs.total_score
              FROM modern_score vs
              INNER JOIN modern_team vc ON vs.cand_id = vc.team_id
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
                'message' => 'Modern Scores for All Judges Fetched Successfully',
                'data' => $groupedScores
            ];
            header("HTTP/1.0 200 OK");
            return json_encode($data);
        }else{
            $data = [
                'status' => 404,
                'message' => 'No Modern Scores Found',
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

// READ - Get Vocal Score by score_id
function getModernScores($scoreParams){
    global $conn;
    
    $score_id = mysqli_real_escape_string($conn, $scoreParams['score_id']);
    
    if(empty(trim($score_id))){
        return error422('Enter score ID');
    }
    
    $query = "SELECT
                vs.score_id,
                vs.cand_id as team_id,
                vc.team,
                vs.judge_id,
                vs.mastery_of_steps,
                vs.choreography_and_style,
                vs.costume_and_props,
                vs.stage_presence,
                vs.audience_impact,
                vs.total_score
              FROM modern_score vs
              INNER JOIN modern_team vc ON vs.cand_id = vc.team_id
              WHERE vs.score_id = '$score_id' LIMIT 1";
    
    $result = mysqli_query($conn, $query);
    
    if($result){
        if(mysqli_num_rows($result) == 1){
            $res = mysqli_fetch_assoc($result);
            
            $data = [
                'status' => 200,
                'message' => 'Modern Score Fetched Successfully',
                'data' => $res
            ];
            header("HTTP/1.0 200 OK");
            return json_encode($data);
        }else{
            $data = [
                'status' => 404,
                'message' => 'No Modern Scores Found',
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

// READ - Get Vocal Score by cand_id
function getModernScoreByCandId($scoreParams){
    global $conn;

    $cand_id = mysqli_real_escape_string($conn, $scoreParams['cand_id']);

    if(empty(trim($cand_id))){
        return error422('Enter candidate ID');
    }

    $query = "SELECT
                vs.score_id,
                vs.cand_id as team_id,
                vc.team,
                vs.judge_id,
                vs.mastery_of_steps,
                vs.choreography_and_style,
                vs.costume_and_props,
                vs.stage_presence,
                vs.audience_impact,
                vs.total_score
              FROM modern_score vs
              INNER JOIN modern_team vc ON vs.cand_id = vc.team_id
              WHERE vs.cand_id = '$cand_id' LIMIT 1";

    $result = mysqli_query($conn, $query);

    if($result){
        if(mysqli_num_rows($result) == 1){
            $res = mysqli_fetch_assoc($result);

            $data = [
                'status' => 200,
                'message' => 'Modern Score Fetched Successfully',
                'data' => $res
            ];
            header("HTTP/1.0 200 OK");
            return json_encode($data);
        }else{
            $data = [
                'status' => 404,
                'message' => 'No Modern Score Found for this Candidate',
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

// READ - Get Vocal Scores by Judge ID
function getModernScoresByJudge($judgeParams){
    global $conn;

    $judge_id = mysqli_real_escape_string($conn, $judgeParams['judge_id']);

    if(empty(trim($judge_id))){
        return error422('Enter judge ID');
    }

    $whereClause = "WHERE vs.judge_id = '$judge_id'";
    if (isset($judgeParams['team']) && !empty(trim($judgeParams['team']))) {
        $team = mysqli_real_escape_string($conn, $judgeParams['team']);
        $whereClause .= " AND vc.cand_team = '$team'";
    }

    $query = "SELECT
                vs.score_id,
                vs.team_id,
                vc.cand_team,
                vs.judge_id,
                vs.mastery_of_steps,
                vs.choreography_and_style,
                vs.costume_and_props,
                vs.stage_presence,
                vs.audience_impact,
                vs.total_score
                vs.created_at
              FROM modern_score vs
              INNER JOIN modern_team vc ON vs.cand_id = vc.cand_id
              $whereClause
              ORDER BY vs.created_at DESC";

    $result = mysqli_query($conn, $query);

    if($result){
        if(mysqli_num_rows($result) > 0){
            $res = mysqli_fetch_all($result, MYSQLI_ASSOC);

            $data = [
                'status' => 200,
                'message' => 'Modern Scores for Judge Fetched Successfully',
                'data' => $res
            ];
            header("HTTP/1.0 200 OK");
            return json_encode($data);
        }else{
            $data = [
                'status' => 404,
                'message' => 'No Modern Scores Found for this Judge',
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