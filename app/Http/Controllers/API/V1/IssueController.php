<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIssueRequest;
use App\Http\Requests\UpdateIssueRequest;
use App\Http\Resources\IssueResource;
use Aws\DynamoDb\DynamoDbClient;
use Illuminate\Http\Request;

class IssueController extends Controller
{
    protected $dynamoDb;
    protected $tableName;

    public function __construct(DynamoDbClient $dynamoDb)
    {
        $this->dynamoDb = $dynamoDb;
        $this->tableName = 'Issues';

    }

    /**
     * @OA\Post(
     *     path="/api/v1/issues/table",
     *     tags={"Issues"},
     *     summary="Create issue table structure",
     *     description="Return list of issues",
     *     @OA\Response(
     *         response=200,
     *         description="Succesful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/IssueResource")
     *         )
     *      )
     * )
     */

    public function createTableStructure()
    {

        echo "# Creating table $this->tableName...\n";
        try {
            $response = $this->dynamoDb->createTable([
                'TableName' => $this->tableName,
                'AttributeDefinitions' => [
                    ['AttributeName' => 'IssueId', 'AttributeType' => 'S'],
                    ['AttributeName' => 'Title', 'AttributeType' => 'S'],
                    ['AttributeName' => 'CreateDate', 'AttributeType' => 'S'],
                    ['AttributeName' => 'DueDate', 'AttributeType' => 'S'],
                ],
                'KeySchema' => [
                    ['AttributeName' => 'IssueId', 'KeyType' => 'HASH'], //Partition key
                    ['AttributeName' => 'Title', 'KeyType' => 'RANGE'], //Sort key
                ],
                'GlobalSecondaryIndexes' => [
                    [
                        'IndexName' => 'CreateDateIndex',
                        'KeySchema' => [
                            ['AttributeName' => 'CreateDate', 'KeyType' => 'HASH'], //Partition key
                            ['AttributeName' => 'IssueId', 'KeyType' => 'RANGE'], //Sort key
                        ],
                        'Projection' => [
                            'ProjectionType' => 'INCLUDE',
                            'NonKeyAttributes' => ['Description', 'Status'],
                        ],
                        'ProvisionedThroughput' => [
                            'ReadCapacityUnits' => 1, 'WriteCapacityUnits' => 1,
                        ],
                    ],
                    [
                        'IndexName' => 'TitleIndex',
                        'KeySchema' => [
                            ['AttributeName' => 'Title', 'KeyType' => 'HASH'], //Partition key
                            ['AttributeName' => 'IssueId', 'KeyType' => 'RANGE'], //Sort key
                        ],
                        'Projection' => ['ProjectionType' => 'KEYS_ONLY'],
                        'ProvisionedThroughput' => [
                            'ReadCapacityUnits' => 1, 'WriteCapacityUnits' => 1,
                        ],
                    ],
                    [
                        'IndexName' => 'DueDateIndex',
                        'KeySchema' => [
                            ['AttributeName' => 'DueDate', 'KeyType' => 'HASH'], //Partition key
                        ],
                        'Projection' => [
                            'ProjectionType' => 'ALL',
                        ],
                        'ProvisionedThroughput' => [
                            'ReadCapacityUnits' => 1, 'WriteCapacityUnits' => 1],
                    ],
                ],
                'ProvisionedThroughput' => [
                    'ReadCapacityUnits' => 1, 'WriteCapacityUnits' => 1],
            ]);

            echo "  Waiting for table $this->tableName to be created.\n";
            $this->dynamoDb->waitUntil('TableExists', [
                'TableName' => $this->tableName,
                '@waiter' => [
                    'delay' => 5,
                    'maxAttempts' => 20,
                ],
            ]);
            echo "  Table $this->tableName has been created.\n";
        } catch (DynamoDbException $e) {
            echo $e->getMessage() . "\n";
            exit("Unable to create table $this->tableName\n");
        }

    }

    /**
     * @OA\Get(
     *     path="/api/v1/issues",
     *     tags={"Issues"},
     *     summary="Get list of issues",
     *     description="Return list of issues",
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="Issue Id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             default="A-101"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="create_date",
     *         in="query",
     *         description="Issue Create Date",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             default="2014-11-19"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Sort",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *              enum={"asc", "desc"},
     *             default="asc"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             default=1
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             default=10
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Succesful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/IssueResource")
     *         )
     *      )
     * )
     */
    public function index(Request $request)
    {

        $response = $this->dynamoDb->query([
            'TableName' => $this->tableName,
            'IndexName' => 'CreateDateIndex',
            //'KeyConditionExpression' => 'CreateDate = :v_dt and begins_with(IssueId, :v_issue)',
            //'ExpressionAttributeValues' => [
            //    ':v_dt' => ['S' => $request->create_date ? $request->create_date : '2014-11-01'],
            //    ':v_issue' => ['S' => $request->id ? $request->id : 'A-'],
            //],

            'KeyConditionExpression' => 'CreateDate = :v_dt',
            'ExpressionAttributeValues' => [
                ':v_dt' => ['S' => $request->create_date ? $request->create_date : '2014-11-01'],
            ],

            /*
            'KeyConditionExpression' => 'begins_with(IssueId, :v_issue)',
            'ExpressionAttributeValues' => [
            ':v_issue' => ['S' => $request->id ? $request->id : 'A-'],
            ],
             */

            'ScanIndexForward' => $request->sort == 'asc' ? true : false,
        ]);

        $data = [];
        foreach ($response['Items'] as $item) {

            $data[] = [
                'id' => $item['IssueId']['S'],
                'title' => $item['Title']['S'],
                'description' => $item['Description']['S'],
                'create_date' => $item['CreateDate']['S'],
                //'last_update_date' => $item['LastUpdateDate']['S'],
                //'due_date' => $item['DueDate']['S'],
                //'priority' => $item['Priority']['N'],
                'status' => $item['Status']['S'],
            ];
        }

        //return ApiResponseHelper::sendResponse(IssueResource::collection($data), '', 200);
        return response()->json($data);

        // ########################################
        // Query for issues filed on 2014-11-01

        $response = $this->dynamoDb->query([
            'TableName' => $this->tableName,
            'IndexName' => 'CreateDateIndex',
            'KeyConditionExpression' =>
            'CreateDate = :v_dt and begins_with(IssueId, :v_issue)',
            'ExpressionAttributeValues' => [
                ':v_dt' => ['S' => '2014-11-01'],
                ':v_issue' => ['S' => 'A-'],
            ],
        ]);

        echo '# Query for issues filed on 2014-11-01:' . "\n";
        foreach ($response['Items'] as $item) {
            echo ' - ' . $item['CreateDate']['S'] .
                ' ' . $item['IssueId']['S'] .
                ' ' . $item['Description']['S'] .
                ' ' . $item['Status']['S'] .
                "\n";
        }

        echo "\n";

        // ########################################
        // Query for issues that are 'Compilation errors'

        $response = $this->dynamoDb->query([
            'TableName' => $this->tableName,
            'IndexName' => 'TitleIndex',
            'KeyConditionExpression' => 'Title = :v_title and IssueId >= :v_issue',
            'ExpressionAttributeValues' => [
                ':v_title' => ['S' => 'Compilation error'],
                ':v_issue' => ['S' => 'A-'],
            ],
        ]);

        echo '# Query for issues that are compilation errors: ' . "\n";

        foreach ($response['Items'] as $item) {
            echo ' - ' . $item['Title']['S'] .
                ' ' . $item['IssueId']['S'] .
                "\n";
        }

        echo "\n";

        // ########################################
        // Query for items that are due on 2014-11-30

        $response = $this->dynamoDb->query([
            'TableName' => $this->tableName,
            'IndexName' => 'DueDateIndex',
            'KeyConditionExpression' => 'DueDate = :v_dt',
            'ExpressionAttributeValues' => [
                ':v_dt' => ['S' => '2014-11-30'],
            ],
        ]);

        echo "# Querying for items that are due on 2014-11-30:\n";
        foreach ($response['Items'] as $item) {
            echo ' - ' . $item['DueDate']['S'] .
                ' ' . $item['IssueId']['S'] .
                ' ' . $item['Title']['S'] .
                ' ' . $item['Description']['S'] .
                ' ' . $item['CreateDate']['S'] .
                ' ' . $item['LastUpdateDate']['S'] .
                ' ' . $item['Priority']['N'] .
                ' ' . $item['Status']['S'] . "\n";
        }

        echo "\n";
    }

    /**
     * @OA\Get(
     *     path="/api/v1/issues/{id}",
     *     tags={"Issues"},
     *     summary="Get issue information",
     *     description="Get issue details by ID (A-101)",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *
     *         required=true,
     *         @OA\Schema(type="string", default="A-101")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(type="Object",ref="#/components/schemas/IssueResource")
     *     )
     * )
     */

    public function show(string $id)
    {

        //return ApiResponseHelper::sendResponse(IssueResource::collection($data), '', 200);
        //return response()->json($data);

        //echo "\n\n";
        //echo "# Getting an item from table $this->tableName...\n";

        $response = $this->dynamoDb->getItem([
            'TableName' => $this->tableName,
            'ConsistentRead' => true,
            'Key' => [
                'IssueId' => [
                    'S' => $id,
                ],
                'Title' => [
                    'S' => 'Compilation error',
                ],

            ],
            //'ProjectionExpression' => 'IssueId, Title, Description',
        ]);

        //print_r($response['Item']);

        $data = [
            'id' => $response['Item']['IssueId']['S'],
            'title' => $response['Item']['Title']['S'],
            'description' => $response['Item']['Description']['S'],
        ];

        return response()->json($data);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/issues",
     *     tags={"Issues"},
     *     summary="Create new issue",
     *     description="Create a new issue record",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id", "title"},
     *             @OA\Property(property="id", type="string", example="A-101"),
     *             @OA\Property(property="title", type="string", example="Network issue"),
     *             @OA\Property(property="description", type="string", example="Can't ping IP address 127.0.0.1. Please fix this."),
     *             @OA\Property(property="create_date", type="string", example="2014-11-19"),
     *             @OA\Property(property="due_date", type="string", example="2014-11-19"),
     *             @OA\Property(property="priority", type="integer", example=5),
     *             @OA\Property(property="status", type="string", example="Assigned")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Record created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/IssueResource")
     *     )
     * )
     */

    public function store(StoreIssueRequest $request)
    {
        $response = $this->dynamoDb->putItem([
            'TableName' => $this->tableName,
            'Item' => [
                'IssueId' => ['S' => $request->id],
                'Title' => ['S' => $request->title],
                'Description' => [
                    'S' => $request->description],
                'CreateDate' => ['S' => $request->create_date],
                'LastUpdateDate' => ['S' => '2014-11-19'],
                'DueDate' => ['S' => $request->due_date],
                //'Priority' => ['N' => `$request->priority`],
                'Priority' => ['N' => (string) $request->priority],
                'Status' => ['S' => $request->status],
            ],
        ]);

    }

    /**
     * @OA\Post(
     *     path="/api/v1/issues/sample",
     *     tags={"Issues"},
     *     summary="Create sample Issue",
     *     description="Create a new issue record",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id", "title"},
     *             @OA\Property(property="id", type="string", example="A-101"),
     *             @OA\Property(property="title", type="string", example="Network issue"),
     *             @OA\Property(property="description", type="string", example="Can't ping IP address 127.0.0.1. Please fix this."),
     *             @OA\Property(property="create_date", type="string", example="2014-11-19"),
     *             @OA\Property(property="due_date", type="string", example="2014-11-19"),
     *             @OA\Property(property="priority", type="integer", example=5),
     *             @OA\Property(property="status", type="string", example="Assigned")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Record created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/IssueResource")
     *     )
     * )
     */

    public function storeSample(StoreIssueRequest $request)
    {
        $response = $this->dynamoDb->putItem([
            'TableName' => $this->tableName,
            'Item' => [
                'IssueId' => ['S' => 'A-101'],
                'Title' => ['S' => 'Compilation error'],
                'Description' => [
                    'S' => 'Can\'t compile Project X - bad version number. What does this mean?'],
                'CreateDate' => ['S' => '2014-11-01'],
                'LastUpdateDate' => ['S' => '2014-11-02'],
                'DueDate' => ['S' => '2014-11-10'],
                'Priority' => ['N' => '1'],
                'Status' => ['S' => 'Assigned'],
            ],
        ]);

        $response = $this->dynamoDb->putItem([
            'TableName' => $this->tableName,
            'Item' => [
                'IssueId' => ['S' => 'A-102'],
                'Title' => ['S' => 'Can\'t read data file'],
                'Description' => [
                    'S' => 'The main data file is missing, or the permissions are incorrect'],
                'CreateDate' => ['S' => '2014-11-01'],
                'LastUpdateDate' => ['S' => '2014-11-04'],
                'DueDate' => ['S' => '2014-11-30'],
                'Priority' => ['N' => '2'],
                'Status' => ['S' => 'In progress'],
            ],
        ]);

        $response = $this->dynamoDb->putItem([
            'TableName' => $this->tableName,
            'Item' => [
                'IssueId' => ['S' => 'A-103'],
                'Title' => ['S' => 'Test failure'],
                'Description' => [
                    'S' => 'Functional test of Project X produces errors.'],
                'CreateDate' => ['S' => '2014-11-01'],
                'LastUpdateDate' => ['S' => '2014-11-02'],
                'DueDate' => ['S' => '2014-11-10'],
                'Priority' => ['N' => '1'],
                'Status' => ['S' => 'In progress'],
            ],
        ]);

        $response = $this->dynamoDb->putItem([
            'TableName' => $this->tableName,
            'Item' => [
                'IssueId' => ['S' => 'A-104'],
                'Title' => ['S' => 'Compilation error'],
                'Description' => [
                    'S' => 'Variable "messageCount" was not initialized.'],
                'CreateDate' => ['S' => '2014-11-15'],
                'LastUpdateDate' => ['S' => '2014-11-16'],
                'DueDate' => ['S' => '2014-11-30'],
                'Priority' => ['N' => '3'],
                'Status' => ['S' => 'Assigned'],
            ],
        ]);

        $response = $this->dynamoDb->putItem([
            'TableName' => $this->tableName,
            'Item' => [
                'IssueId' => ['S' => 'A-105'],
                'Title' => ['S' => 'Network issue'],
                'Description' => [
                    'S' => 'Can\'t ping IP address 127.0.0.1. Please fix this.'],
                'CreateDate' => ['S' => '2014-11-15'],
                'LastUpdateDate' => ['S' => '2014-11-16'],
                'DueDate' => ['S' => '2014-11-19'],
                'Priority' => ['N' => '5'],
                'Status' => ['S' => 'Assigned'],
            ],
        ]);

    }

    /**
     * @OA\Put(
     *     path="/api/v1/issues/{id}",
     *     tags={"Issues"},
     *     summary="Update issue information",
     *     description="Update issue record by ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(default="A-101",type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "description"},
     *             @OA\Property(property="title", type="string", example="Compilation error"),
     *             @OA\Property(property="description", type="string", example="Can't compile Project X - bad version number. What does this mean?")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Record updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/IssueResource")
     *     )
     * )
     */
    public function update(UpdateIssueRequest $request, string $id)
    {
        //echo "\n\n";
        //echo "# Updating an item attribute only if it has not changed in table $this->tableName...\n";

        // A-101

        $response = $this->dynamoDb->updateItem([
            'TableName' => $this->tableName,
            'Key' => [
                'IssueId' => [
                    'S' => $id,
                ],
                'Title' => [
                    'S' => $request->title,
                ],
            ],
            'ExpressionAttributeNames' => [
                '#P' => 'Description',
            ],
            'ExpressionAttributeValues' => [
                ':val1' => ['S' => $request->description],
                //':val2' => ['S' => 'Can\'t compile Project X - bad version number. What does this mean?'],
            ],
            'UpdateExpression' => 'set #P = :val1',
            //'ConditionExpression' => '#P = :val2',
            'ReturnValues' => 'ALL_NEW',
        ]);

        //print_r($response['Attributes']);

        return '';
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/issues/{id}",
     *     tags={"Issues"},
     *     summary="Delete issue record",
     *     description="Delete issue by ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Record deleted successfully"
     *     )
     * )
     */
    public function destroy($id)
    {

        echo "\n\n";
        echo "# Deleting an item and returning its previous values from in table $this->tableName...\n";

        $response = $this->dynamoDb->deleteItem([
            'TableName' => $this->tableName,
            'Key' => [
                'IssueId' => [
                    'S' => $id,
                ],
                'Title' => [
                    'S' => 'Network issue',
                ],
            ],
            'ReturnValues' => 'ALL_OLD',
        ]);

        //print_r($response['Attributes']);
        return '';
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/issues/{id}/table",
     *     tags={"Issues"},
     *     summary="Delete issue table",
     *     description="Delete issue by ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Record deleted successfully"
     *     )
     * )
     */
    public function destroyTable($id)
    {
        // ########################################
        // Delete the table

        try {
            echo "# Deleting table $this->tableName...\n";
            $this->dynamoDb->deleteTable(['TableName' => $this->tableName]);
            echo "  Waiting for table $this->tableName to be deleted.\n";

            $this->dynamoDb->waitUntil('TableNotExists', [
                'TableName' => $this->tableName,
                '@waiter' => [
                    'delay' => 5,
                    'maxAttempts' => 20,
                ],
            ]);

            echo "  Table $this->tableName has been deleted.\n";
        } catch (DynamoDbException $e) {
            echo $e->getMessage() . "\n";
            exit("Unable to delete table $this->tableName\n");
        }

    }
}
