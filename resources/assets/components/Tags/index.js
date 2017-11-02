import React from 'react';
import { map } from 'lodash';
import PropTypes from 'prop-types';
import classnames from 'classnames';

import './tags.scss';

class Tag extends React.Component {
  constructor() {
    super();

    this.state = {
      'sending': false,
    }

    this.handleClick = this.handleClick.bind(this);
  }

  handleClick(label) {
    if (label === 'Hide In Gallery 👻') {
      label = 'Hide In Gallery';
    }

    this.setState({ 'sending' : true });

    this.props.isClicked(this.props.post, label)
      .then(() => {
        this.setState({ 'sending' : false });
      });
  }

  render() {
    return <button className={classnames('tag', {'is-active': this.props.isActive}, {'is-loading': this.state.sending})}
                onClick={() => this.handleClick(this.props.label)}>{this.props.label}</button>
  }
}

Tag.propTypes = {
  isClicked: PropTypes.func.isRequired,
  post: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
  isActive: PropTypes.bool.isRequired,
  label: PropTypes.string.isRequired,
};

class Tags extends React.Component {
  render() {
    const tags = {
      'good-photo': 'Good Photo',
      'good-quote': 'Good Quote',
      'hide-in-gallery': 'Hide In Gallery 👻',
      'good-for-sponsor': 'Good For Sponsor',
      'good-for-storytelling': 'Good For Storytelling',
    };

    return (
      <div>
        <h4>Tags</h4>
        <ul className="aligned-actions">
          {map(tags, (label, key) => (
            <li key={key}>
              <Tag isActive={this.props.tagged.includes(key)} isClicked={this.props.onTag} label={label} post={this.props.id} />
            </li>
          ))}
        </ul>
      </div>
    )
  }
}

Tags.propTypes = {
  tagged: PropTypes.bool.isRequired,
  onTag: PropTypes.func.isRequired,
  id: PropTypes.int.isRequired,
};

export default Tags;
