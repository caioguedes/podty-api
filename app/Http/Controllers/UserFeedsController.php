<?php
namespace App\Http\Controllers;

use App\Repositories\UserEpisodesRepository;
use App\Repositories\UserFeedsRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Input;

/**
 * Class UserController
 *
 * @package App\Http\Controllers
 */
class UserFeedsController extends ApiController
{
    /**
     * Get all feeds from specific user
     * @param string $username
     */
    public function all($username)
    {
        return $this->responseData(UserFeedsRepository::all($username));
    }

    public function one($username, $feedId)
    {
        return $this->responseData(UserFeedsRepository::one($username, $feedId));
    }

    public function attach($username, $feedId)
    {
        $user = UserRepository::first($username);
        if (!$user) {
            return $this->respondNotFound();
        }

        $userFeed = UserFeedsRepository::create($feedId, $user);

        if (!$userFeed) {
            return $this->setStatusCode(Response::HTTP_BAD_GATEWAY)->respondError('');
        }

        UserEpisodesRepository::createAllEpisodesFromUserFeed($userFeed);
        UserFeedsRepository::markAllNotListened($userFeed->id);

        return $this->respondSuccess(['created' => true]);
    }

    public function detach($username, $feedId)
    {
        $user = UserRepository::first($username);
        if (!$user) {
            return $this->respondNotFound();
        }

        $deleted = UserFeedsRepository::delete($feedId, $user);

        // TODO apagar tudo de UserEpisodes

        return $deleted ?
            $this->respondSuccess(['removed' => true]) :
            $this->respondNotFound();
    }



    public function listenAll($username, $feedId)
    {
        $userId = UserRepository::getId($username);
        if (!$userId) {
            return $this->respondNotFound('user not found');
        }

        $userFeed = UserFeedsRepository::first($feedId, $userId);

        if (!$userFeed) {
            return $this->respondNotFound('fedd not found for given user');
        }

        UserEpisodesRepository::deleteAll($userFeed->id);
        UserFeedsRepository::markAllListened($userFeed->id);

        return $this->respondSuccess(['mark all as listened' => true]);
    }


    private function responseData($data)
    {
        return empty($data) ? $this->respondNotFound() : $this->respondSuccess($data);
    }
}
