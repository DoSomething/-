import React from 'react';
import { BrowserRouter, Route, Switch, Redirect } from 'react-router-dom';
import { ApolloProvider } from '@apollo/react-hooks';

import { env } from './helpers';
import graphql from './graphql';
import ShowPost from './pages/ShowPost';
import ShowUser from './pages/ShowUser';
import UserIndex from './pages/UserIndex';
import ShowSignup from './pages/ShowSignup';
import ShowCampaign from './pages/ShowCampaign';
import CampaignIndex from './pages/CampaignIndex';

const Application = () => {
  const endpoint = env('GRAPHQL_URL');

  return (
    <ApolloProvider client={graphql(endpoint)}>
      <BrowserRouter>
        <Switch>
          <Route path="/campaigns" exact>
            <CampaignIndex />
          </Route>
          <Redirect from="/campaigns/:id" exact to="/campaigns/:id/accepted" />
          <Redirect from="/campaigns/:id/inbox" to="/campaigns/:id/pending" />
          <Route path="/campaigns/:id/:status">
            <ShowCampaign />
          </Route>
          <Route path="/users" exact>
            <UserIndex />
          </Route>
          <Route path="/users/:id">
            <ShowUser />
          </Route>
          <Route path="/posts/:id">
            <ShowPost />
          </Route>
          <Route path="/signups/:id">
            <ShowSignup />
          </Route>
        </Switch>
      </BrowserRouter>
    </ApolloProvider>
  );
};

export default Application;