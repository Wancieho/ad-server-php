<?php

use Jacwright\RestServer\RestException;

class CampaignsController {

    /**
     * @url GET /
     */
    public function index() {
        return CampaignsModel::get();
    }

    /**
     * @url GET /$id
     */
    public function read($id = null) {
        return CampaignsModel::get($id);
    }

    /**
     * @url POST /
     */
    public function create() {
        if (!isset($_POST['name'])) {
            throw new RestException(401, '`name` field must be specified');
        }

        return CampaignsModel::save(new Campaign((object) [
                            'name' => $_POST['name'],
        ]));
    }

    /**
     * @url DELETE /$id
     */
    public function delete($id = null) {
        return CampaignsModel::delete($id);
    }

}
