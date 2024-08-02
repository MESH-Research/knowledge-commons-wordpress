import React from 'react';
import {Link, withRouter} from "react-router-dom";
import {SearchForm} from "./SearchForm";

class NotFound extends React.Component {
    constructor(props) {
        super(props);
    }
    componentDidMount() {
        window.scrollTo(0, 0);
        document.title = document.title+" | Page Not Found";
    }
    render() {
        return (<div className={'page-not-found container'} role="main" id="main-content">
            <h1 className={'middle-center'}>Page Not Found</h1>
            <div className={'middle-center'}>
                <span>We couldn’t find the page you were looking for. Try searching below, or browsing artifacts by <Link
                to="/keyword">keyword</Link>.</span>
            </div>
            <SearchForm history={this.props.history}/>
        </div>)
    }
}

export default withRouter(NotFound);
