<?php

class CryptoBot
{
    /**
     * Initialize application
     */
    public function init() {

        $this->twitter_client = new TwitterClient(Codebird::getInstance());

        $this->crypto_client = new CryptoClient(new CurlClient, [
            'api' => CRYPTO_API,
            'endpoint' => CRYPTO_API_ENDPOINT,
            'params' => [
                'limit' => CRYPTO_API_LIMIT
            ]
        ]);
    }

    /**
     * Run the application
     */
    public function run() {
        $this->dataset = $this->getDataset();

        $this->formatData();

        $tweets = $this->createTweets();

        if (!$this->postTweets($tweets)) {
            $this->deleteTweets($this->failed_tweets);
        }
    }

    /**
     * Get the Crypto data
     *
     * @return array
     */
    protected function getDataset() : array {
        return $this->crypto_client->getData();
    }


    /**
     * Format the Crypto data to an array of strings
     *
     * @throws CryptoStatusException if Crypto data is missing
     */
    protected function formatData() {
        $this->dataset = array_map(function (array $data) {
            if (isset($data['rank'], $data['symbol'], $data['name'], $data['price_usd'], $data['price_btc'], $data['percent_change_1h'])) {
                $data['name'] = $this->camelCase($data['name']);
                $data['price_usd'] = $this->removeTrailingZeros(number_format($data['price_usd'], 2));
                $data['price_btc'] = $this->removeTrailingZeros(number_format($data['price_btc'], 6));

                return "#{$data['rank']} #{$data['symbol']} (#{$data['name']}): {$data['price_usd']} USD | {$data['price_btc']} BTC | {$data['percent_change_1h']}% 1h";
            }

            throw new CryptoStatusException('Crypto data is missing', 1);
        }, $this->dataset);
    }

    /**
     * Create the Tweets with Crypto data and return them as an array
     *
     * @return array
     */
    protected function createTweets() : array {
        $tweets = [];
        $start_rank = 1;
        $end_rank = 5;
        $length = 5;

        for ($i = 0; $i < 5; $i++) {
            $tweets[$i] = "Current Status of the Crypto Realm: (#{$start_rank} to #{$end_rank}):\n\n";
            $tweets[$i] .= implode("\n\n", array_slice($this->dataset, $start_rank - 1, $length));

            $start_rank += 5;
            $end_rank += 5;

            if ($i == 1) {
                $end_rank++;
                $length++;
            }
        }

        return $tweets;
    }

    /**
     * Post the specified Tweets
     *
     * @param array $tweets The Tweets to post
     * @return bool
     */
    protected function postTweets(array $tweets) : bool {
        $last_tweet_id = null;

        for ($i = 0; $i < 5; $i++) {
            if ($last_tweet_id) {
                $tweet = $this->twitter_client->postTweet([
                    'status' => '@' . TWITTER_SCREENNAME . ' ' . $tweets[$i],
                    'in_reply_to_status_id' => $last_tweet_id
                ], [ 'id' ]);
            } else {
                $tweet = $this->twitter_client->postTweet([
                    'status' => $tweets[$i]
                ], [ 'id' ]);
            }

            if (isset($tweet['id'])) {
                $tweet_ids[] = $last_tweet_id = $tweet['id'];
            } else {
                break;
            }
        }

        if (count($tweet_ids) == 5) {
            return true;
        } else {
            $this->failed_tweets = $tweet_ids;

            return false;
        }
    }

    /**
     * Delete the specified Tweets
     *
     * @param array $tweet_ids The IDs of the Tweets to delete
     * @throws CryptoStatusException if Tweets could not be deleted
     */
    protected function deleteTweets(array $tweet_ids) {
        $deleted_counter = 0;

        foreach ($tweet_ids as $tweet_id) {
            $deleted = $this->twitter_client->deleteTweet($tweet_id);

            if ($deleted) {
                $deleted_counter++;
            }
        }

        if ($deleted_counter != count($tweet_ids)) {
            throw new CryptoStatusException('Deleting Tweets failed', 2);
        }
    }



}
