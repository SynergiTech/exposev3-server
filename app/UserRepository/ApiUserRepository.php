<?php

namespace Expose\Server\UserRepository;

use Expose\Server\Contracts\ConnectionManager;
use Expose\Server\Contracts\UserRepository;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

class ApiUserRepository implements UserRepository
{
    protected PendingRequest $client;

    public function __construct(
        protected ConnectionManager $connectionManager
    ) {
        $baseUrl = config('expose-server.api_user_repository.url');
        $apiKey = config('expose-server.api_user_repository.api_key');

        if ($baseUrl === null || $apiKey === null) {
            throw new \RuntimeException('Missing configuration for Api User Repository');
        }

        $this->client = Http::withHeaders([
            'X-Api-Key' => $apiKey,
        ])
            ->baseUrl($baseUrl)
            ->acceptJson();
    }

    protected function getUserDetails(array $user): array
    {
        $user['sites'] = [];
        $user['tcp_connections'] = [];

        if (array_key_exists('auth_token', $user) && $user['auth_token'] !== '') {
            $user['sites'] = $this->connectionManager->getConnectionsForAuthToken($user['auth_token']);
            $user['tcp_connections'] = $this->connectionManager->getTcpConnectionsForAuthToken($user['auth_token']);
        }

        return $user;
    }

    protected function formatUsers(array $users): array
    {
        return array_map(
            function ($user) {
                return $this->getUserDetails($user);
            },
            $users
        );
    }

    public function getUsers(): PromiseInterface
    {
        return new Promise(function (callable $resolve, callable $reject) {
            try {
                $users = [];

                // do {
                    $response = $this->client
                        ->get(
                            '/all',
                            [
                                'limit' => 100,
                            ]
                        )
                        ->throw();

                    $users = array_merge($users, $response->json('data'));
                // } while ($response->json('meta.current_page') < $response->json('meta.last_page'));

                $resolve($this->formatUsers($users));
            } catch (\Throwable $e) {
                $reject($e);
            }
        });
    }

    public function userCount(): PromiseInterface
    {
		return new Promise(function (callable $resolve, callable $reject) {
            try {
                $users = [];

                // do {
                    $response = $this->client
                        ->get(
                            '/all', // todo why not count yourself?
                            [
                                'limit' => 100,
                            ]
                        )
                        ->throw();

                    $users = array_merge($users, $response->json('data'));
                // } while ($response->json('meta.current_page') < $response->json('meta.last_page'));

                $resolve(count($users)); // is this correct?
            } catch (\Throwable $e) {
                $reject($e);
            }
        });
    }

    public function getUserById($id): PromiseInterface
    {
        return new Promise(function (callable $resolve, callable $reject) use ($id) {
            try {
                $response = $this->client
                    ->get("/user/{$id}")
                    ->throw();

                $resolve($this->getUserDetails($response->json('data')));
            } catch (\Exception $e) {
                $reject($e);
            }
        });
    }

    public function paginateUsers(string $searchQuery, int $perPage, int $currentPage): PromiseInterface
    {
        return new Promise(function (callable $resolve, callable $reject) use ($searchQuery, $perPage, $currentPage) {
            try {
                $response = $this->client
                    ->get(
                        '/all',
                        [
                            'limit' => $perPage,
                            'page' => $currentPage,
                            'search' => $searchQuery
                        ]
                    )
                    ->throw();

                $nextPage = ($response->json('meta.current_page') < $response->json('meta.last_page'))
                    ? $currentPage + 1
                    : null;

                $paginated = [
                    'total' => $response->json('meta.total'),
                    'users' => $this->formatUsers($response->json('data')),
                    'current_page' => $currentPage,
                    'per_page' => $perPage,
                    'next_page' => $nextPage,
                    'previous_page' => $currentPage > 1 ? $currentPage - 1 : null,
                ];

                $resolve($paginated);
            } catch (\Exception $e) {
                $reject($e);
            }
        });
    }

    public function getUserByToken(string $authToken): PromiseInterface
    {
        return new Promise(function (callable $resolve, callable $reject) use ($authToken) {
            try {
                $response = $this->client
                    ->get("/user/token/{$authToken}")
                    ->throw();

                $resolve($this->getUserDetails($response->json('data')));
            } catch (\Exception $e) {
                $reject($e);
            }
        });
    }

    public function storeUser(array $data): PromiseInterface
    {
        return new Promise(function (callable $resolve, callable $reject) {
            $reject(new \RuntimeException('not implementing'));
        });
    }

    public function deleteUser($id): PromiseInterface
    {
        return new Promise(function (callable $resolve, callable $reject) {
            $resolve(false);
        });
    }

    public function getUsersByTokens(array $authTokens): PromiseInterface
    {
        return new Promise(function (callable $resolve, callable $reject) use ($authTokens) {
            try {
                $response = $this->client
                    ->post('/all', ['tokens' => $authTokens])
                    ->throw();

                $resolve($this->getUserDetails($response->json('data')));
            } catch (\Exception $e) {
                $reject($e);
            }
        });
    }

    public function updateLastSharedAt($id): PromiseInterface
    {
        return new Promise(function (callable $resolve, callable $reject) {
            $resolve();
        });
    }

    public function setCooldownForToken(string $authToken, int $cooldownEndsAt): PromiseInterface
    {
        return new Promise(function (callable $resolve, callable $reject) {
            $resolve();
        });
    }
}
