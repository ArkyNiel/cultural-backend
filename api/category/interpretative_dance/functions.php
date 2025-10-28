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

    if (!isset($scoreInput['cand_id']) || empty(trim($scoreInput['cand_id']))) {
        return error422('Enter candidate ID');
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

    $cand_id = mysqli_real_escape_string($conn, $scoreInput['cand_id']);
    $judge_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);
    $originality = mysqli_real_escape_string($conn, $scoreInput['originality']);
    $mastery_of_steps = mysqli_real_escape_string($conn, $scoreInput['mastery_of_steps']);
    $choreography_and_style = mysqli_real_escape_string($conn, $scoreInput['choreography_and_style']);
    $costume_and_props = mysqli_real_escape_string($conn, $scoreInput['costume_and_props']);
    $stage_presence = mysqli_real_escape_string($conn, $scoreInput['stage_presence']);

    // Generate unique score_id
    do {
        $score_id = rand(100000, 999999);
        $checkQuery = "SELECT score_id FROM interpretative_dance WHERE score_id = '$score_id'";
        $checkResult = mysqli_query($conn, $checkQuery);
    } while (mysqli_num_rows($checkResult) > 0);

    $query = "INSERT INTO interpretative_dance (score_id, cand_id, judge_id, originality, mastery_of_steps, choreography_and_style, costume_and_props, stage_presence)
              VALUES ('$score_id', '$cand_id', '$judge_id', '$originality', '$mastery_of_steps', '$choreography_and_style', '$costume_and_props', '$stage_presence')";
    $result = mysqli_query($conn, $query);

    if($result){
        // Calculate average total_score from all judges submitted so far for this contestant
        $avg_query = "SELECT AVG(total_score) AS avg_total FROM interpretative_dance WHERE cand_id = '$cand_id'";
        $avg_result = mysqli_query($conn, $avg_query);
        $avg_row = mysqli_fetch_assoc($avg_result);
        $avg_total = $avg_row['avg_total'];

        // The final score is the average total_score (sum of all judges' total_scores divided by number of judges)
        $percentage = $avg_total;

        // Check if interpretative_final_score row exists for this cand_id
        $check_query = "SELECT cand_id FROM interpretative_final_score WHERE cand_id = '$cand_id'";
        $check_result = mysqli_query($conn, $check_query);
        if (mysqli_num_rows($check_result) > 0) {
            // Update existing row
            $update_query = "UPDATE interpretative_final_score SET final_score = '$percentage' WHERE cand_id = '$cand_id'";
            mysqli_query($conn, $update_query);
        } else {
            // Insert new row
            $insert_query = "INSERT INTO interpretative_final_score (cand_id, final_score) VALUES ('$cand_id', '$percentage')";
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

// READ - Get All Interpretative Scores
function getAllInterpretativeScores($params = []){
    global $conn;

    $whereClause = "";
    if (isset($params['team']) && !empty(trim($params['team']))) {
        $team = mysqli_real_escape_string($conn, $params['team']);
        $whereClause = "WHERE c.cand_team = '$team'";
    }

    $query = "SELECT
                id.score_id,
                id.cand_id,
                c.cand_name,
                c.cand_team,
                id.judge_id,
                id.originality,
                id.mastery_of_steps,
                id.choreography_and_style,
                id.costume_and_props,
                id.stage_presence,
                id.total_score
              FROM interpretative_dance id
              INNER JOIN contestants c ON id.cand_id = c.cand_id
              $whereClause
              ORDER BY id.judge_id, id.total_score DESC";

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
                id.score_id,
                id.cand_id,
                c.cand_name,
                c.cand_team,
                id.originality,
                id.mastery_of_steps,
                id.choreography_and_style,
                id.costume_and_props,
                id.stage_presence,
                id.total_score
              FROM interpretative_dance id
              INNER JOIN contestants c ON id.cand_id = c.cand_id
              WHERE id.score_id = '$score_id' LIMIT 1";

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

// READ - Get Interpretative Score by cand_id
function getInterpretativeScoreByCandId($scoreParams){
    global $conn;

    $cand_id = mysqli_real_escape_string($conn, $scoreParams['cand_id']);

    if(empty(trim($cand_id))){
        return error422('Enter candidate ID');
    }

    $query = "SELECT
                id.score_id,
                id.cand_id,
                c.cand_name,
                c.cand_team,
                id.originality,
                id.mastery_of_steps,
                id.choreography_and_style,
                id.costume_and_props,
                id.stage_presence,
                id.total_score,
                id.created_at
              FROM interpretative_dance id
              INNER JOIN contestants c ON id.cand_id = c.cand_id
              WHERE id.cand_id = '$cand_id' LIMIT 1";

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
                'message' => 'No Interpretative Score Found for this Candidate',
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


// UPDATE INTERPRETATIVE SCORE *ONLY THE CHAIRMAN CAN UPDATE OR EDIT
function updateInterpretativeScore($scoreInput){
    global $conn;

    $score_id = mysqli_real_escape_string($conn, $scoreInput['score_id']);
    $originality = mysqli_real_escape_string($conn, $scoreInput['originality']);
    $mastery_of_steps = mysqli_real_escape_string($conn, $scoreInput['mastery_of_steps']);
    $choreography_and_style = mysqli_real_escape_string($conn, $scoreInput['choreography_and_style']);
    $costume_and_props = mysqli_real_escape_string($conn, $scoreInput['costume_and_props']);
    $stage_presence = mysqli_real_escape_string($conn, $scoreInput['stage_presence']);

    if(empty(trim($score_id))){
        return error422('Enter score ID');
    }elseif(empty(trim($originality))){
        return error422('Enter originality score');
    }elseif(empty(trim($mastery_of_steps))){
        return error422('Enter mastery of steps score');
    }elseif(empty(trim($choreography_and_style))){
        return error422('Enter choreography and style score');
    }elseif(empty(trim($costume_and_props))){
        return error422('Enter costume and props score');
    }elseif(empty(trim($stage_presence))){
        return error422('Enter stage presence score');
    }else{

        $query = "UPDATE interpretative_dance SET
                    originality = '$originality',
                    mastery_of_steps = '$mastery_of_steps',
                    choreography_and_style = '$choreography_and_style',
                    costume_and_props = '$costume_and_props',
                    stage_presence = '$stage_presence'
                  WHERE score_id = '$score_id' LIMIT 1";

        $result = mysqli_query($conn, $query);

        if($result){
            $data = [
                'status' => 200,
                'message' => 'Interpretative Score Updated Successfully',
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
function deleteInterpretativeScore($scoreInput){
    global $conn;

    $score_id = mysqli_real_escape_string($conn, $scoreInput['score_id']);

    if(empty(trim($score_id))){
        return error422('Enter score ID');
    }

    $query = "DELETE FROM interpretative_dance WHERE score_id = '$score_id' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if($result){
        $data = [
            'status' => 200,
            'message' => 'Interpretative Score Deleted Successfully',
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

// READ - Get Interpretative Scores by Judge ID
function getInterpretativeScoresByJudge($judgeParams){
    global $conn;

    $judge_id = mysqli_real_escape_string($conn, $judgeParams['judge_id']);

    if(empty(trim($judge_id))){
        return error422('Enter judge ID');
    }

    $whereClause = "WHERE id.judge_id = '$judge_id'";
    if (isset($judgeParams['team']) && !empty(trim($judgeParams['team']))) {
        $team = mysqli_real_escape_string($conn, $judgeParams['team']);
        $whereClause .= " AND c.cand_team = '$team'";
    }

    $query = "SELECT
                id.score_id,
                id.cand_id,
                c.cand_name,
                c.cand_team,
                id.originality,
                id.mastery_of_steps,
                id.choreography_and_style,
                id.costume_and_props,
                id.stage_presence,
                id.total_score,
                id.created_at
              FROM interpretative_dance id
              INNER JOIN contestants c ON id.cand_id = c.cand_id
              $whereClause
              ORDER BY id.created_at DESC";

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
