<?php
namespace Peanut\Queue\Adapter;

use Aws\Sqs\SqsClient;

class Sqs // implements \Peanut\Queue\AdapterInterface
{
    const STOP_INSTRUCTION = 'STOP';

    private $sqsClient;
    private $sourceQueueUrl;
    private $failedQueueUrl;
    private $errorQueueUrl;
    private $maxWaitingSeconds;
    private $visibilityTimeout;
    public function __construct(SqsClient $sqsClient, $queueName, $maxWaitingSeconds = 20, $visibilityTimeout = 43200)
    {
        $this->setSqsClient($sqsClient);
        $this->setMaxWaitingSeconds($maxWaitingSeconds);
        $this->setVisibilityTimeout($visibilityTimeout);
        $this->setQueueUrls($queueName);
    }
    public function setSqsClient(SqsClient $sqsClient)
    {
        $this->sqsClient = $sqsClient;

        return $this;
    }
    public function getSqsClient()
    {
        return $this->sqsClient;
    }
    public function setVisibilityTimeout($visibilityTimeout)
    {
        $this->visibilityTimeout = $visibilityTimeout;

        return $this;
    }
    public function setMaxWaitingSeconds($maxWaitingSeconds)
    {
        $this->maxWaitingSeconds = $maxWaitingSeconds;

        return $this;
    }
    public function setSourceQueueUrl($queueUrl)
    {
        $this->sourceQueueUrl = $queueUrl;

        return $this;
    }
    public function getSourceQueueUrl()
    {
        return $this->sourceQueueUrl;
    }
    public function setFailedQueueUrl($queueUrl)
    {
        $this->failedQueueUrl = $queueUrl;

        return $this;
    }
    public function setErrorQueueUrl($queueUrl)
    {
        $this->errorQueueUrl = $queueUrl;

        return $this;
    }
    public function getNextQueue()
    {
        $queueItem = $this->sqsClient->receiveMessage([
            'QueueUrl'            => $this->sourceQueueUrl,
            'MaxNumberOfMessages' => 1,
            'WaitTimeSeconds'     => $this->maxWaitingSeconds,
            'VisibilityTimeout'   => $this->visibilityTimeout,
        ]);
        if ($queueItem->hasKey('Messages')) {
            return $queueItem->get('Messages')[0];
        }

        return false;
    }
    public function sendJob($job)
    {
        return $this->sendMessage($this->sourceQueueUrl, $job);
    }
    public function successful($job)
    {
        $this->deleteMessage($this->sourceQueueUrl, $job['ReceiptHandle']);
    }
    public function failed($job)
    {
        $this->sendMessage($this->failedQueueUrl, $job['Body']);
        $this->deleteMessage($this->sourceQueueUrl, $job['ReceiptHandle']);
    }
    public function error($job)
    {
        $this->sendMessage($this->errorQueueUrl, $job['Body']);
        $this->deleteMessage($this->sourceQueueUrl, $job['ReceiptHandle']);
    }
    public function stopped($job)
    {
        $this->deleteMessage($this->sourceQueueUrl, $job['ReceiptHandle']);
    }
    public function nothingToDo($job)
    {
    }
    public function getMessageBody($job)
    {
        return $job['Body'] ?? false;
    }
    public function isRunning()
    {
        return true;
    }
    public function isValidJob()
    {
        return $job !== false;
    }
    public function isStopJob($job)
    {
        return self::STOP_INSTRUCTION === $this->getMessageBody($job);
    }
    public function start(\Closure $callback)
    {
        $this->log('debug', 'Starting Queue Worker!');
        while ($this->isRunning()) {
            try {
                $job = $this->getNextQueue();
            } catch (\Exception $exception) {
                $this->logger->error('Error getting data. Message: '.$exception->getMessage());
                continue;
            }
            try {
                if (true === $this->isValidJob($job)) {
                    if (true === $this->isStopJob($job)) {
                        $this->logger->debug('STOP instruction received.');
                        $this->stopped($job);
                    } else {
                        if ($callback($this->getMessageBody($job))) {
                            $this->logger->debug('Successful Job: '.$this->toString($job));
                            $this->successful($job);
                        } else {
                            $this->logger->debug('Failed Job:'.$this->toString($job));
                            $this->failed($job); // delete
                        }
                    }
                } else {
                    $this->logger->debug('Nothing to do.');
                    $this->nothingToDo();
                }
            } catch (\Exception $exception) {
                $this->logger->error('Error Managing data. Data :'.$this->toString($job).'. Message: '.$exception->getMessage());
                $this->error($job); // delete
            }
        }
        $this->logger->debug('Queue Worker finished.');
    }
    public function toString($job)
    {
        return json_encode($job);
    }
    protected function setQueueUrls($queueName)
    {
        $this->setSourceQueueUrl($this->getQueueUrl($queueName));
        $this->setFailedQueueUrl($this->getQueueUrl($queueName.'-failed'));
        $this->setErrorQueueUrl($this->getQueueUrl($queueName.'-error'));
    }
    protected function getQueueUrl($queueName)
    {
        try {
            $queueData = $this->sqsClient->createQueue([
                'VisibilityTimeout' => $this->visibilityTimeout,
                'QueueName'         => $queueName,
            ]);
        } catch (\Aws\Sqs\Exception\SqsException $ex) {
            throw $ex;
        }

        return $queueData->get('QueueUrl');
    }
    protected function deleteMessage($queueUrl, $messageReceiptHandle)
    {
        $this->sqsClient->deleteMessage([
            'QueueUrl'      => $queueUrl,
            'ReceiptHandle' => $messageReceiptHandle,
        ]);
    }
    private function sendMessage($queueUrl, $messageBody)
    {
        return $this->sqsClient->sendMessage([
            'QueueUrl'    => $queueUrl,
            'MessageBody' => $messageBody,
        ]);
    }
}
