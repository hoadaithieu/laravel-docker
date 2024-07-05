<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
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
/*
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
 */
    }

    /**
     * @OA\Get(
     *     path="/api/v1/issues",
     *     tags={"Issues"},
     *     summary="Get list of issues",
     *     description="Return list of issues",
     *     @OA\Response(
     *         response=200,
     *         description="Succesful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/StudentResource")
     *         )
     *      )
     * )
     */
    public function index()
    {
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
            'KeyConditionExpression' =>
            'Title = :v_title and IssueId >= :v_issue',
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
     *     description="Get issue details by ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/IssueResource")
     *     )
     * )
     */
    public function show(string $id)
    {
        echo "\n\n";
        echo "# Getting an item from table $this->tableName...\n";

        $response = $this->dynamoDb->getItem([
            'TableName' => $this->tableName,
            'ConsistentRead' => true,
            'Key' => [
                'IssueId' => [
                    'S' => '120',
                ],
            ],
            'ProjectionExpression' => 'IssueId, Title',
        ]);

        print_r($response['Item']);

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
     *             required={"name", "age"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="age", type="integer", example=20)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Record created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/IssueResource")
     *     )
     * )
     */

    public function store(StoreStudentRequest $request)
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
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "age"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="age", type="integer", example=20)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Record updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/IssueResource")
     *     )
     * )
     */
    public function update(UpdateStudentRequest $request, string $id)
    {
        echo "\n\n";
        echo "# Updating an item attribute only if it has not changed in table $this->tableName...\n";

        $response = $this->dynamoDb->updateItem([
            'TableName' => $this->tableName,
            'Key' => [
                'Id' => [
                    'N' => '121',
                ],
            ],
            'ExpressionAttributeNames' => [
                '#P' => 'Price',
            ],
            'ExpressionAttributeValues' => [
                ':val1' => ['N' => '25'],
                ':val2' => ['N' => '20'],
            ],
            'UpdateExpression' => 'set #P = :val1',
            'ConditionExpression' => '#P = :val2',
            'ReturnValues' => 'ALL_NEW',
        ]);

        print_r($response['Attributes']);
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
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Record deleted successfully"
     *     )
     * )
     */
    public function destroy($id)
    {
        /*
        // ########################################
        // Delete the table

        try {
        echo "# Deleting table $this->tableName...\n";
        $this->dynamoDb->deleteTable(['TableName' => $this->tableName]);
        echo "  Waiting for table $this->tableName to be deleted.\n";

        $this->dynamoDb->waitUntil('TableNotExists', [
        'TableName' => $this->tableName,
        '@waiter' => [
        'delay'       => 5,
        'maxAttempts' => 20
        ]
        ]);

        echo "  Table $this->tableName has been deleted.\n";
        } catch (DynamoDbException $e) {
        echo $e->getMessage() . "\n";
        exit("Unable to delete table $this->tableName\n");
        }
         */
        echo "\n\n";
        echo "# Deleting an item and returning its previous values from in table $this->tableName...\n";

        $response = $this->dynamoDb->deleteItem([
            'TableName' => $this->tableName,
            'Key' => [
                'Id' => [
                    'N' => '121',
                ],
            ],
            'ReturnValues' => 'ALL_OLD',
        ]);

        print_r($response['Attributes']);

    }
}
