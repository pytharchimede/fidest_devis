<?php declare(strict_types=1); namespace App\Http\Controller;
use App\Application\Workflow\GetWorkflowSummary; use App\Http\Request; use App\Http\Response;
final class WorkflowController { public function __construct(private readonly GetWorkflowSummary $summary){} public function summary(Request $request,array $parameters=[]):Response{return Response::json(['data'=>$this->summary->execute()]);} }
