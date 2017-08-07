import React from 'react';
import { flatMap, keyBy, map, sample, forEach, reject, filter } from 'lodash';
import { extractPostsFromSignups } from '../../helpers';

import InboxItem from '../InboxItem';
import ModalContainer from '../ModalContainer';
import HistoryModal from '../HistoryModal';
import PagingButtons from '../PagingButtons';
import PostFilter from '../PostFilter';
import StatusCounter from '../StatusCounter';
import { RestApiClient} from '@dosomething/gateway';

class CampaignSingle extends React.Component {
  constructor(props) {
    super(props);

    this.state = {};

    this.api = new RestApiClient;
    this.updateQuantity = this.updateQuantity.bind(this);
    this.showHistory = this.showHistory.bind(this);
    this.hideHistory = this.hideHistory.bind(this);
    this.filterPosts = this.filterPosts.bind(this);
  }

  componentDidMount() {
    this.api.get('api/v2/posts', {
      filter: {
        status: 'accepted',
        campaign_id: this.props.campaign.id,
      }
    })
    .then(json => this.setState({
      signups: {
        0: {
          campaign_id: 10,
          campaign_run_id: 1744,
          created_at: "2017-07-26 17:31:56",
          id: 128,
          northstar_id: "5978afc27f43c27d8755558c",
          quantity: 3,
          source: "campaigns",
          updated_at: "2017-08-01 20:48:26",
          why_participated: "because",
        },
      },
      posts: json.data,
      filter: 'accepted',
      postTotals: json.meta.pagination.total,
      displayHistoryModal: null,
      historyModalId: null,
      nextPage: json.meta.pagination.links.next ? json.meta.pagination.links.next : null,
      prevPage: json.meta.pagination.links.previous ? json.meta.pagination.links.previous : null,
    }));
  }

  // Filter posts based on status.
  filterPosts(status) {
    let request = this.api.get('api/v2/posts', {
      filter: {
        status: status.toLowerCase(),
        campaign_id: this.props.campaign.id,
      }
    });
    request.then((result) => {
      var posts = keyBy(result.data, 'id');

      // Update the state
      this.setState((previousState) => {
        const newState = {...previousState};

        // newState.signups = signups;
        newState.posts = posts;
        newState.filter = status.toLowerCase();
        newState.postTotals = result.meta.pagination.total;
        newState.nextPage = result.meta.pagination.links.next ? result.meta.pagination.links.next : null;
        newState.prevPage = result.meta.pagination.links.previous ? result.meta.pagination.links.previous : null;

        return newState;
      });
    });
  }

  // Open the history modal of the given post
  // @TODO - Figure out how to share this logic between components so it
  // doesn't need to be duplicated between components.
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

        newState.signups[signup.id].quantity = result.quantity;

        return newState;
      });
    });

    // Close the modal
    this.hideHistory();
  }

  render() {
    const posts = this.state.posts;
    const campaign = this.props.campaign;

    // We need the signup object with all of it's posts. Insteadn of grabbing all of the signups via api, can we do this through database?

    return (
      <div className="container">
        <StatusCounter postTotals={this.props.post_totals} campaign={campaign} />

        <PostFilter onChange={this.filterPosts} />

        { map(posts, (post, key) => post.status === this.state.filter ? <InboxItem allowReview={false} onUpdate={this.updatePost} onTag={this.updateTag} showHistory={this.showHistory} deletePost={this.deletePost} key={key} details={{post: post, campaign: campaign, signup: this.state.signups[post.signup_id]}} /> : null) }

        <ModalContainer>
            {this.state.displayHistoryModal ? <HistoryModal id={this.state.historyModalId} onUpdate={this.updateQuantity} onClose={e => this.hideHistory(e)} details={{post: posts[this.state.historyModalId], campaign: campaign, signups: this.state.signups}}/> : null}
        </ModalContainer>

        <PagingButtons prev={this.state.prevPage} next={this.state.nextPage}></PagingButtons>
      </div>
    )
  }
}

export default CampaignSingle;
