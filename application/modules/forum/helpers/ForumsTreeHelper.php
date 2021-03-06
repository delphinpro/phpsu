<?php


/**
 * ForumsTreeHelper
 *
 * Readonly forums tree helper
 */

namespace modules\forum\helpers;

class ForumsTreeHelper
{


    /**
     * getTree
     *
     * Will return forums tree data
     *
     * @param  int   $forumID Identification number of forum
     * @return array          Forums tree data
     */

    public static function getTree($forumID = null)
    {
        $params = array();
        $filter = '';
        if ($forumID) {
            $params[':forum_id'] = $forumID;
            $filter = 'WHERE ff.id = :forum_id';
        }

        // get forums
        $forums = \DBI::getConnection('slave')->sendQuery(
            "SELECT
                    ff.id,
                    ff.title,
                    ff.description,
                    (NULL) subforums
                FROM forum_forums ff
                INNER JOIN forum_forums_stat ffs
                    ON ffs.forum_id = ff.id
                        AND ffs.subforums_count > 0
                {$filter}
                ORDER BY ff.sort ASC",
            $params
        )->fetchAll(\PDO::FETCH_OBJ);
        // get subforums
        if ($forums) {
            $subForums = SubForumsHelper::getSubForums($forumID);
            foreach ($forums as $forum) {
                $forum->subforums = array();
                foreach ($subForums as $subForum) {
                    if ($subForum->forum_id == $forum->id) {
                        $forum->subforums[] = $subForum;
                    }
                }
            }
        }

        return $forums;
    }
}
