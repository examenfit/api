<?php

namespace App\Clients\EduV;

use Exception;
use Illuminate\Support\Facades\Log;

class EduVClient
{
  private $accessToken;

  public $response;
  public $statusCode;


  public function __construct($accessToken)
  {
    if (!$accessToken) {
      throw new Exception('Access token is required');
    }

    $this->accessToken = $accessToken;
    $this->response = NULL;
    $this->statusCode = NULL;
  }

  private function getUrl($endpoint = '/', $params = array())
  {
    $apiBaseUrl = config('clients.eduv_api_uri');

    $url = $apiBaseUrl . $endpoint;
    if (!empty($params)) {
      $url .= '?' . http_build_query($params);
    }

    return $url;
  }

  private function getHeaders()
  {
    $accessToken = $this->accessToken;
    $contentType = 'application/json';

    $headers = array();
    $headers[] = "Authorization: Bearer $accessToken";
    $headers[] = "Content-Type: $contentType";

    return $headers;
  }

  private function httpGet($url)
  {
    $headers = $this->getHeaders();

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $this->response = curl_exec($ch);
    $this->statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);
  }

  private function httpPost($url, $data)
  {
    $headers = $this->getHeaders();

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $this->response = curl_exec($ch);
    $this->statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);
  }

  private function apiGetJson($endpoint)
  {
    $url = $this->getUrl($endpoint);
    $this->httpGet($url);
    return $this->decodeResponse();
  }

  private function apiPostJson($endpoint, $data)
  {
    $url = $this->getUrl($endpoint);
    $this->httpPost($url, $data);
    return $this->decodeResponse();
  }

  private function decodeResponse() {
    if (300 < $this->statusCode) {
      throw new Exception('Error: ' . $this->statusCode . ' - ' . $this->response);
    }
    try {
      $response = json_decode($this->response, false);
      return $response;
    }
    catch(Exception $e) {
      throw new Exception('Error decoding JSON response: ' . $e->getMessage());
    }
  }

  public function getSchoolPeriods($orgMasterId)
  {
    // get a list of school periods for a teacher
    $endpoint = '/schoolperiods/school?orgMasterId=' . $orgMasterId;
    $response = $this->apiGetJson($endpoint);
    return $response;
  }

  public function getOrganisations()
  {
    // get a list of organisations for a teacher
    $endpoint = '/organisations';
    $response = $this->apiGetJson($endpoint);
    return $response;
  }

  public function getGroups($orgMasterId)
  {
    // get a list of groups for a teacher
    $endpoint = '/groups/school?orgMasterId=' . $orgMasterId;
    $response = $this->apiGetJson($endpoint);
    return $response;
  }

  public function getStudents($orgMasterId)
  {
    // get a list of students for a teacher
    $endpoint = '/students/school?orgMasterId=' . $orgMasterId;
    $response = $this->apiGetJson($endpoint);
    return $response;
  }

  public function getAssignments($orgMasterId)
  {
    // get a list of assignments for a teacher
    $endpoint = '/assignments/school?orgMasterId=' . $orgMasterId;
    $response = $this->apiGetJson($endpoint);
    return $response;
  }

  public function getEmployees($orgMasterId)
  {
    // get a list of employees for a teacher
    $endpoint = '/employees/school?orgMasterId=' . $orgMasterId;
    $response = $this->apiGetJson($endpoint);
    return $response;
  }

  public function getEnrollments($orgMasterId)
  {
    // get a list of enrollments for a teacher
    $endpoint = '/enrollments/school?orgMasterId=' . $orgMasterId;
    $response = $this->apiGetJson($endpoint);
    return $response;
  }

  public function getStudyofferings($orgMasterId)
  {
    // get a list of studyofferings for a teacher
    $endpoint = '/studyofferings/school?orgMasterId=' . $orgMasterId;
    $response = $this->apiGetJson($endpoint);
    return $response;
  }

  public function getSubjectofferings($orgMasterId)
  {
    // get a list of subjectofferings for a teacher
    $endpoint = '/subjectofferings/school?orgMasterId=' . $orgMasterId;
    $response = $this->apiGetJson($endpoint);
    return $response;
  }

  public function getClasses($orgMasterId)
  {
    $classes = array();

    $schoolperiods = $this->getSchoolPeriods($orgMasterId);
    $studyofferings = $this->getStudyofferings($orgMasterId);
    $subjectofferings = $this->getSubjectofferings($orgMasterId);
    $enrollments = $this->getEnrollments($orgMasterId);
    $assignments = $this->getAssignments($orgMasterId);
    $employees = $this->getEmployees($orgMasterId);
    $students = $this->getStudents($orgMasterId);

    $schoolperiodsMapping = array();
    foreach($schoolperiods as $schoolperiod) {
      $schoolperiodsMapping[$schoolperiod->schoolPeriodId] = $schoolperiod;
    }

    $studyofferingsMapping = array();
    foreach($studyofferings as $studyOffering) {
      $studyofferingsMapping[$studyOffering->studyOfferingId] = $studyOffering;
    }

    $subjectofferingsMapping = array();
    foreach($subjectofferings as $subjectOffering) {
      $subjectofferingsMapping[$subjectOffering->subjectOfferingId] = $subjectOffering;
    }

    $assignmentsMapping = array();
    foreach($assignments as $assignment) {
      $assignmentsMapping[$assignment->assignmentId] = $assignment;
    }

    $employeesMapping = array();
    foreach($employees as $employee) {
      $employeesMapping[$employee->userMasterIdentifier] = $employee;
    }

    $studentsMapping = array();
    foreach($students as $student) {
      $studentsMapping[$student->userMasterIdentifier] = $student;
    }

    $groups = $this->getGroups($orgMasterId);
    foreach($groups as $group) {
      if ($group->status != 'active') {
        continue;
      }
      $period = $schoolperiodsMapping[$group->schoolPeriod];
      $schoolPeriod = array(
	'schoolPeriodId' => $period->schoolPeriodId,
	'title' => $period->title,
	'startDate' => $period->startDate,
	'endDate' => $period->endDate,
      );
      $class = array(
        'groupId' => $group->groupId,
        'groupName' => $group->groupName,
	'students' => array(),
	'employees' => array(),
        'schoolperiod' => $schoolPeriod,
      );

      // get students for group
      foreach($group->students as $studentRef) {
        $student = $studentsMapping[$studentRef->userMasterIdentifier];
        if ($student->status != 'active') {
	  continue;
	}
        $studentEnrollments = array();
        foreach($enrollments as $enrollment) {
          if ($enrollment->student->userMasterIdentifier != $student->userMasterIdentifier) {
            continue;
          }
	  if ($enrollment->status != 'active') {
	    continue;
	  }
          $studentEnrollment = array(
	    'enrollmentId' => $enrollment->enrollmentId,
            'studyName' => $studyofferingsMapping[$enrollment->study]->studyName,
            'studyCode' => $studyofferingsMapping[$enrollment->study]->studyCode,
	    'subjectOfferingAbbr' => $subjectofferingsMapping[$enrollment->subject]->subjectOfferingAbbr,
	    'subjectOfferingName' => $subjectofferingsMapping[$enrollment->subject]->subjectOfferingName,
	  );
          if ($studyofferingsMapping[$enrollment->study]->studyLevel) {
            $studentEnrollment['studyLevelName'] = $studyofferingsMapping[$enrollment->study]->studyLevel->studyLevelName;
          }
	  $studentEnrollments[] = $studentEnrollment;
	}
        $class['students'][] = array(
          'userMasterIdentifier' => $student->userMasterIdentifier,
          'givenName' => $student->givenName,
          'familyName' => $student->familyName,
          'familyNamePrefix' => $student->familyNamePrefix,
          'email' => $student->email,
          'enrollments' => $studentEnrollments,
        );
      }

      foreach($group->assignments as $assignmentId) {
	$assignment = $assignmentsMapping[$assignmentId];
        if ($assignment->status != 'active') {
	  continue;
	}
        if ($assignment->assignmentType	 != 'class-teacher') {
	  continue;
	}
        $employee = $employeesMapping[$assignment->employee->userMasterIdentifier];
        if ($employee->status != 'active') {
	  continue;
	}
	$class['employees'][] = array(
          'userMasterIdentifier' => $employee->userMasterIdentifier,
          'givenName' => $employee->givenName,
          'familyName' => $employee->familyName,
          'familyNamePrefix' => $employee->familyNamePrefix,
          'email' => $employee->email,
        );
      }

      $classes[] = $class;
    }
    return $classes;
  }
}
