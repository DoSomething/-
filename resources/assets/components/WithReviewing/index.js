import React from 'react';
import { keyBy } from 'lodash';
import { RestApiClient } from '@dosomething/gateway';
import { extractPostsFromSignups, extractSignupsFromPosts } from '../../helpers';

const reviewComponent = (Component, data) => {
  return class extends React.Component {
    constructor(props) {
      super(props);

      this.state = {
        loading: true,
      };

      this.api = new RestApiClient;
      this.updatePost = this.updatePost.bind(this);
      this.updateTag = this.updateTag.bind(this);
      this.updateQuantity = this.updateQuantity.bind(this);
      this.showHistory = this.showHistory.bind(this);
      this.hideHistory = this.hideHistory.bind(this);
      this.deletePost = this.deletePost.bind(this);
      this.setNewPosts = this.setNewPosts.bind(this);
    }

    // Loads initial posts into state.
    componentDidMount() {
      // Require initial_posts and campaign parameters.
      if (data.initial_posts && data.campaign) {
        this.getPostsByStatus(data.initial_posts, data.campaign.id);
      } else {
        // @TODO - handle error better.
        console.log("Error: need to know the initial posts to load and the campaign.");
      }
    }

    // Make API call to GET /posts to get posts by filtered status.
    getPostsByStatus(status, campaignId) {
      this.api.get('api/v2/posts', {
        filter: {
          status: status,
          campaign_id: campaignId,
        },
        include: 'signup,siblings',
      })
      .then(json => this.setState({
        campaign: data.campaign,
        posts: keyBy(json.data, 'id'),
        signups: extractSignupsFromPosts(keyBy(json.data, 'id')),
        filter: status,
        postTotals: json.meta.pagination.total,
        displayHistoryModal: null,
        historyModalId: null,
        loading: false,
        nextPage: json.meta.pagination.links.next,
        prevPage: json.meta.pagination.links.previous,
      }));
    }

    setNewPosts(posts, next, prev) {
      this.setState({
        campaign: data.campaign,
        posts: posts,
        signups: extractSignupsFromPosts(posts),
        filter: status,
        // postTotals: json.meta.pagination.total,
        displayHistoryModal: null,
        historyModalId: null,
        loading: false,
        nextPage: next,
        prevPage: prev,
      });
    }

    // Open the history modal of the given post
    showHistory(postId, event) {
      event.preventDefault()

      this.setState({
        displayHistoryModal: true,
        historyModalId: postId,
      });
    }

    // Close the open history modal
    hideHistory(event) {
      if (event) {
        event.preventDefault();
      }

      this.setState({
        displayHistoryModal: false,
        historyModalId: null,
      });
    }

    // Updates a post status.
    updatePost(postId, fields) {
      fields.post_id = postId;

      let request = this.api.put('reviews', fields);

      request.then((result) => {
        this.setState((previousState) => {
          const newState = {...previousState};
          newState.posts[postId].status = fields.status;

          return newState;
        });
      });
    }

    // Tag a post.
    updateTag(postId, tag) {
      const fields = {
        post_id: postId,
        tag_name: tag,
      };

      let response = this.api.post('tags', fields);

      return response.then((result) => {
        this.setState((previousState) => {
          const newState = {...previousState};
          const user = newState.posts[postId].user;
          const signup = newState.posts[postId].signup.data;

          // Merge existing post with the newly updated values from API.
          newState.posts[postId] = {
            ...newState.posts[postId],
            ...result['data']
          };

          return newState;
        });
      });
    }

    // Update a signups quanity.
    updateQuantity(signup, newQuantity) {
      // Fields to send to /posts
      const fields = {
        northstar_id: signup.northstar_id,
        campaign_id: signup.campaign_id,
        campaign_run_id: signup.campaign_run_id,
        quantity: newQuantity,
      };

      // Make API request to Rogue to update the quantity on the backend
      let request = this.api.post('posts', fields);

      request.then((result) => {
        // Update the state
        this.setState((previousState) => {
          const newState = {...previousState};

          newState.signups[signup.signup_id].quantity = result.quantity;

          return newState;
        });
      });

      // Close the modal
      this.hideHistory();
    }

    // Delete a post.
    deletePost(postId, event) {
      event.preventDefault();
      const confirmed = confirm('🚨🔥🚨Are you sure you want to delete this?🚨🔥🚨');

      if (confirmed) {
        // Make API request to Rogue to update the quantity on the backend
        let response = this.api.delete('posts/'.concat(postId));

        response.then((result) => {
          // Update the state
          this.setState((previousState) => {
            var newState = {...previousState};

            // Remove the deleted post from the state
            delete(newState.posts[postId]);

            // Return the new state
            return newState;
          });
        });
      }
    }

    render() {
      const methods = {
        updatePost: this.updatePost,
        updateTag: this.updateTag,
        updateQuantity: this.updateQuantity,
        showHistory: this.showHistory,
        hideHistory: this.hideHistory,
        deletePost: this.deletePost,
        setNewPosts: this.setNewPosts,
      };

      // Pass in the state from this HoC to trigger rendering down the DOM
      // Also pass in methods bound to this instance.
      // Also pass in original data pass to the HoC in case other components still need it down the line.
      return <Component {...this.state} {...methods} {...data} />;
    }
  }
}

export default reviewComponent;

