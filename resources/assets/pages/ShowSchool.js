import gql from 'graphql-tag';
import React, { useState } from 'react';
import { useParams } from 'react-router-dom';
import { useQuery } from '@apollo/react-hooks';

import NotFound from './NotFound';
import Shell from '../components/utilities/Shell';
import MetaInformation from '../components/utilities/MetaInformation';

const SHOW_SCHOOL_QUERY = gql`
  query ShowSchoolQuery($id: String!) {
    school(id: $id) {
      id
      name
      city
      state
    }
  }
`;

const ShowSchool = () => {
  const { id } = useParams();
  const title = `School #${id}`;

  const { loading, error, data } = useQuery(SHOW_SCHOOL_QUERY, {
    variables: { id },
  });

  if (error) {
    return <Shell error={error} />;
  }

  if (loading) {
    return <Shell title={title} loading />;
  }

  if (!data.school) return <NotFound title={title} type="school" />;

  return (
    <Shell
      title={data.school.name}
      subtitle={`${data.school.city}, ${data.school.state}`}
    >
      <div className="container__row">
        <div className="container__block -third">
          <MetaInformation details={{ ID: id }} />
        </div>
      </div>
    </Shell>
  );
};

export default ShowSchool;
