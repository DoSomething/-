import React from 'react';
import gql from 'graphql-tag';
import { Link } from 'react-router-dom';
import { parse, format } from 'date-fns';
import { useParams } from 'react-router-dom';
import { useQuery } from '@apollo/react-hooks';

import TextBlock from '../components/utilities/TextBlock';
import Shell from '../components/utilities/Shell';
import MetaInformation from '../components/utilities/MetaInformation';
import ReviewablePostGallery from '../components/ReviewablePostGallery';
import DeleteSignupButton from '../components/DeleteSignupButton';
import UserInformation, {
  UserInformationFragment,
} from '../components/utilities/UserInformation';

const SHOW_SIGNUP_QUERY = gql`
  query ShowCampaignQuery($id: Int!) {
    signup(id: $id) {
      id
      groupId
      whyParticipated
      source
      sourceDetails
      referrerUserId
      createdAt
      deleted

      user {
        id
        displayName
        ...UserInformation
      }

      campaign {
        id
        internalTitle
      }
    }
  }

  ${UserInformationFragment}
`;

const ShowCampaign = () => {
  const { id } = useParams();

  const title = `Signup #${id}`;
  const { loading, error, data } = useQuery(SHOW_SIGNUP_QUERY, {
    variables: { id: parseInt(id) },
  });

  if (loading) {
    return <Shell title={title} loading />;
  }

  if (error) {
    return <Shell error={error} />;
  }

  const { signup } = data;
  const exists = signup && !signup.deleted;
  const subtitle = exists
    ? `${signup.user.displayName} / ${signup.campaign.internalTitle}`
    : 'Not found.';

  return (
    <Shell title={title} subtitle={subtitle}>
      <div className="mb-4 clearfix">
        {exists ? (
          <>
            <div className="container__block -half">
              <UserInformation user={signup.user} />
              <div className="mb-4">
                <MetaInformation
                  details={{
                    'User ID': (
                      <Link to={`/users/${signup.user.id}`}>
                        {signup.user.id}
                      </Link>
                    ),
                    Source: (
                      <span>
                        {signup.source}{' '}
                        {signup.sourceDetails ? (
                          <span class="footnote">({signup.sourceDetails})</span>
                        ) : null}
                      </span>
                    ),
                    'Created At': format(
                      parse(signup.createdAt),
                      'M/D/YYYY h:m A',
                    ),
                    Referrer: signup.referrerUserId ? (
                      <Link to={`/users/${signup.referrerUserId}`}>
                        {signup.referrerUserId}
                      </Link>
                    ) : (
                      '-'
                    ),
                    Group: signup.groupId ? (
                      <Link to={`/groups/${signup.groupId}`}>
                        {signup.groupId}
                      </Link>
                    ) : (
                      '-'
                    ),
                  }}
                />
              </div>
              <TextBlock
                title="Why Statement"
                content={signup.whyParticipated}
              />
            </div>
            <div className="container__block -half">
              <ul className="form-actions -inline">
                <li>
                  <button className="button -tertiary" disabled>
                    Create Post
                  </button>
                </li>

                <li>
                  <DeleteSignupButton signup={signup} />
                </li>
              </ul>
            </div>
          </>
        ) : (
          <div className="container__block">
            <p>This signup could not be found. Maybe it was deleted?</p>
          </div>
        )}
      </div>
      <ReviewablePostGallery signupId={id} />
    </Shell>
  );
};

export default ShowCampaign;
