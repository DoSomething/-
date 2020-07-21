import { get } from 'lodash';
import gql from 'graphql-tag';
import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@apollo/react-hooks';

import Empty from './Empty';
import EntityLabel from './utilities/EntityLabel';
import { formatDateTime, updateQuery } from '../helpers';

const POSTS_INDEX_QUERY = gql`
  query PostsIndexQuery($referrerUserId: String, $cursor: String) {
    posts: paginatedPosts(
      after: $cursor
      first: 50
      referrerUserId: $referrerUserId
    ) {
      edges {
        cursor
        node {
          id
          createdAt
          userId
          type
          status
          campaign {
            id
            internalTitle
          }
          groupId
          group {
            id
            name
          }
        }
      }
      pageInfo {
        endCursor
        hasNextPage
      }
    }
  }
`;

/**
 * This component handles fetching & paginating a list of posts.
 *
 * @param {String} referrerUserId
 */
const PostsTable = ({ campaignId, groupId, referrerUserId }) => {
  const { error, loading, data, fetchMore } = useQuery(POSTS_INDEX_QUERY, {
    variables: { campaignId, groupId, referrerUserId },
    notifyOnNetworkStatusChange: true,
  });

  const posts = data ? data.posts.edges : [];
  const noResults = posts.length === 0 && !loading;
  const { endCursor, hasNextPage } = get(data, 'posts.pageInfo', {});

  const handleViewMore = () => {
    fetchMore({
      variables: { cursor: endCursor },
      updateQuery,
    });
  };

  if (error) {
    return (
      <div className="text-center">
        <p>There was an error. :(</p>

        <code>{JSON.stringify(error)}</code>
      </div>
    );
  }

  if (noResults && !hasNextPage) {
    return <Empty copy="No posts found." />;
  }

  return (
    <>
      <table className="table">
        <thead>
          <tr>
            <td>Created</td>

            <td>User</td>

            {campaignId ? null : <td>Campaign</td>}

            <td>Type</td>

            <td>Status</td>

            {groupId ? null : <td>Group</td>}
          </tr>
        </thead>
        <tbody>
          {posts.map(({ node, cursor }) => (
            <tr key={node.id}>
              <td>
                <Link to={`/posts/${node.id}`}>
                  {formatDateTime(node.createdAt)}
                </Link>
              </td>

              <td>
                <Link to={`/users/${node.userId}`}>{node.userId}</Link>
              </td>

              {campaignId ? null : (
                <td>
                  <EntityLabel
                    id={node.campaign.id}
                    name={node.campaign.internalTitle}
                    path="campaigns"
                  />
                </td>
              )}

              <td>{node.type}</td>

              <td>{node.status}</td>

              {groupId ? null : (
                <td>
                  {node.groupId ? (
                    <EntityLabel
                      id={node.group.id}
                      name={node.group.name}
                      path="groups"
                    />
                  ) : null}
                </td>
              )}
            </tr>
          ))}
        </tbody>
        <tfoot className="form-actions">
          {loading ? (
            <tr>
              <td colSpan={campaignId || groupId ? 5 : 6}>
                <div className="spinner margin-horizontal-auto margin-vertical" />
              </td>
            </tr>
          ) : null}
          {hasNextPage ? (
            <tr>
              <td colSpan={campaignId || groupId ? 5 : 6}>
                <button
                  className="button -tertiary"
                  onClick={handleViewMore}
                  disabled={loading}
                >
                  view more...
                </button>
              </td>
            </tr>
          ) : null}
        </tfoot>
      </table>
    </>
  );
};

export default PostsTable;
