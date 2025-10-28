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

// store modern score
function storeModernScore($scoreInput){
    global $conn;

    if (!isset($_SESSION['user_id'])) {
        return error422('User not logged in');
    }

    if (!isset($scoreInput['cand_id']) || empty(trim($scoreInput['cand_id']))) {
        return error422('Enter candidate ID');
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

    // Generate unique score_id
    do {
        $score_id = rand(100000, 999999);
        $checkQuery = "SELECT score_id FROM modern_score WHERE score_id = '$score_id'";
        $checkResult = mysqli_query($conn, $checkQuery);
    } while (mysqli_num_rows($checkResult) > 0);

    $query = "INSERT INTO modern_score (score_id, cand_id, judge_id, mastery_of_steps, choreography_and_style, costume_and_props, stage_presence, audience_impact)
              VALUES ('$score_id', '$cand_id', '$judge_id', '$mastery_of_steps', '$choreography_and_style', '$costume_and_props', '$stage_presence', '$audience_impact')";
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

// READ - Get All Modern Scores
function getAllModernScores($params = []){
    global $conn;

    $whereClause = "";
    if (isset($params['team']) && !empty(trim($params['team']))) {
        $team = mysqli_real_escape_string($conn, $params['team']);
        $whereClause = "WHERE c.cand_team = '$team'";
    }

    $query = "SELECT
                ms.score_id,
                ms.cand_id,
                c.cand_name,
                c.cand_team,
                ms.judge_id,
                ms.mastery_of_steps,
                ms.choreography_and_style,
                ms.costume_and_props,
                ms.stage_presence,
                ms.audience_impact,
                ms.total_score
              FROM modern_score ms
              INNER JOIN contestants c ON ms.cand_id = c.cand_id
              $whereClause
              ORDER BY ms.judge_id, ms.total_score DESC";

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

// READ - Get Modern Score by score_id
function getModernScores($scoreParams){
    global $conn;

    $score_id = mysqli_real_escape_string($conn, $scoreParams['score_id']);

    if(empty(trim($score_id))){
        return error422('Enter score ID');
    }

    $query = "SELECT
                ms.score_id,
                ms.cand_id,
                c.cand_name,
                c.cand_team,
                ms.mastery_of_steps,
                ms.choreography_and_style,
                ms.costume_and_props,
                ms.stage_presence,
                ms.audience_impact,
                ms.total_score
              FROM modern_score ms
              INNER JOIN contestants c ON ms.cand_id = c.cand_id
              WHERE ms.score_id = '$score_id' LIMIT 1";

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

// READ - Get Modern Score by cand_id
function getModernScoreByCandId($scoreParams){
    global $conn;

    $cand_id = mysqli_real_escape_string($conn, $scoreParams['cand_id']);

    if(empty(trim($cand_id))){
        return error422('Enter candidate ID');
    }

    $query = "SELECT
                ms.score_id,
                ms.cand_id,
                c.cand_name,
                c.cand_team,
                ms.mastery_of_steps,
                ms.choreography_and_style,
                ms.costume_and_props,
                ms.stage_presence,
                ms.audience_impact,
                ms.total_score,
                ms.created_at
              FROM modern_score ms
              INNER JOIN contestants c ON ms.cand_id = c.cand_id
              WHERE ms.cand_id = '$cand_id' LIMIT 1";

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


// UPDATE MODERN SCORE *ONLY THE CHAIRMAN CAN UPDATE OR EDIT
function updateModernScore($scoreInput){
    global $conn;

    $score_id = mysqli_real_escape_string($conn, $scoreInput['score_id']);
    $mastery_of_steps = mysqli_real_escape_string($conn, $scoreInput['mastery_of_steps']);
    $choreography_and_style = mysqli_real_escape_string($conn, $scoreInput['choreography_and_style']);
    $costume_and_props = mysqli_real_escape_string($conn, $scoreInput['costume_and_props']);
    $stage_presence = mysqli_real_escape_string($conn, $scoreInput['stage_presence']);
    $audience_impact = mysqli_real_escape_string($conn, $scoreInput['audience_impact']);

    if(empty(trim($score_id))){
        return error422('Enter score ID');
    }elseif(empty(trim($mastery_of_steps))){
        return error422('Enter mastery of steps score');
    }elseif(empty(trim($choreography_and_style))){
        return error422('Enter choreography and style score');
    }elseif(empty(trim($costume_and_props))){
        return error422('Enter costume and props score');
    }elseif(empty(trim($stage_presence))){
        return error422('Enter stage presence score');
    }elseif(empty(trim($audience_impact))){
        return error422('Enter audience impact score');
    }else{

        $query = "UPDATE modern_score SET
                    mastery_of_steps = '$mastery_of_steps',
                    choreography_and_style = '$choreography_and_style',
                    costume_and_props = '$costume_and_props',
                    stage_presence = '$stage_presence',
                    audience_impact = '$audience_impact'
                  WHERE score_id = '$score_id' LIMIT 1";

        $result = mysqli_query($conn, $query);

        if($result){
            $data = [
                'status' => 200,
                'message' => 'Modern Score Updated Successfully',
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

// DELETE
function deleteModernScore($scoreInput){
    global $conn;

    $score_id = mysqli_real_escape_string($conn, $scoreInput['score_id']);

    if(empty(trim($score_id))){
        return error422('Enter score ID');
    }

    $query = "DELETE FROM modern_score WHERE score_id = '$score_id' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if($result){
        $data = [
            'status' => 200,
            'message' => 'Modern Score Deleted Successfully',
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

// READ - Get Modern Scores by Judge ID
function getModernScoresByJudge($judgeParams){
    global $conn;

    $judge_id = mysqli_real_escape_string($conn, $judgeParams['judge_id']);

    if(empty(trim($judge_id))){
        return error422('Enter judge ID');
    }

    $whereClause = "WHERE ms.judge_id = '$judge_id'";
    if (isset($judgeParams['team']) && !empty(trim($judgeParams['team']))) {
        $team = mysqli_real_escape_string($conn, $judgeParams['team']);
        $whereClause .= " AND c.cand_team = '$team'";
    }

    $query = "SELECT
                ms.score_id,
                ms.cand_id,
                c.cand_name,
                c.cand_team,
                ms.mastery_of_steps,
                ms.choreography_and_style,
                ms.costume_and_props,
                ms.stage_presence,
                ms.audience_impact,
                ms.total_score,
                ms.created_at
              FROM modern_score ms
              INNER JOIN contestants c ON ms.cand_id = c.cand_id
              $whereClause
              ORDER BY ms.created_at DESC";

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
