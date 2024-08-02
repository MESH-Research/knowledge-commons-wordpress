import React from 'react';
import {Link} from "react-router-dom";
import {q_popularKeyword, q_searchByGenre, q_searchByGenreKeyword} from "../graphql/bin/queries";
import {ScaleLoader} from "react-spinners";

class KeywordsPage extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            keywordData: null,
            curatorData: null,
            data_ready: false
        };
        this.getData();
    }

    componentDidMount() {
        window.scrollTo(0, 0);
        if(!this.props.hideTitle) {
            document.title = "Digital Pedagogy | Keywords";
        }
    }

    /*
        The getData method pulls in the data from the associated queries, formats (where applicable) and writes data to state.
    */
    getData = () => {
        q_popularKeyword(null, 100).then((result) => {
            q_searchByGenreKeyword('keyword', true, 59).then((result2) => {
                // Returned keywords, which contains curator data for each keyword.
                // Map through the curator data to return a new object for later use within the listing
                const curatorData = result2.data.dp_genres.edges[0].node.artifacts.edges.map((object) => {
                    return {
                        [object.node.title]: object.node.dp_authors.edges.map((obj) => {

                            return <>{obj.node.name}{obj.node.affiliation && <>, <i>{obj.node.affiliation}</i></>}</>;
                        })
                    };
                });
                return this.setState({keywordData: result.data.dp_keywords, data_ready: true, curatorData});
            }).catch(function (e) {
                console.log("Promise Rejected get_data():q_searchByGenreKeyword");
                console.log(JSON.stringify(e));
            });
        }).catch(function (e) {
            console.log("Promise Rejected get_data():q_popularKeyword");
            console.log(JSON.stringify(e));
        });
    };

    // The actual keyword component that spits our the html wrapped keyword data (with curator) as li
    KeywordList = () => {

        // Arrow function to lookup the curator from keyword name, returning the author Li.
        const getCuratorNames = (keyword) => {
            // filter out the pre-cached curator data against the provided keyword name. Only leaves the match in the object.
            const kwObj = this.state.curatorData.filter((object) => !!object[keyword]);

            //we split the curator names into an array so we can create li's for them.
            let authors = [];
            kwObj.forEach(nObj => {
                authors.push(nObj[keyword]);
            });

            // now we map the array and slot the name into the actual li tags.
            return authors.map((curator_name, index) => {
                return (curator_name.map((names, index) => <li key={index}
                                                               className={'keyword__curator-list-item'}>{names}</li>));
            });
        };

        return this.state.keywordData.edges.map((kw, index) => {
            // we map through each keyword to create teh wrapper li.
            return (
                <li key={index} className="list-item col-sm-6 col-md-3 py-2">
                    <Link to={`/keyword/${kw.node.name.replace(" ", "-")}`}>
                        <div>{kw.node.name}</div>
                        <small>
                            {/* Curator data is not in the keyword data so we need to pass the keyword name to lookup the curator by keyword.*/}
                            <ul className={'keyword__curator-list'}>{getCuratorNames(kw.node.name)}</ul>
                        </small>
                    </Link>
                </li>
            )
        })
    };

    render() {
        // load the spinner if the data isn't ready.

        if (!this.state.data_ready) {
            return (!this.props.fullWidth && <div>
                <div className="w-100 p-5 middle-center">
                    <ScaleLoader
                        sizeUnit={"px"}
                        size={75}
                        color={'#7eafbc'}
                        loading={!this.state.data_ready}/>
                </div>
            </div>);
        }

        return (<> {/* <> is React's pseudo div */}
            <div className={'keywords col-10 mx-auto'} role="main" id="main-content">
                {!this.props.hideTitle && <h1 className={'keywords__title pb-3'}>Browse keywords</h1>}
                <h3 className='curator-link pb-5 mb-5 text-center'><a href={'/curators'}><u>Or browse by curator</u></a></h3>
                <div className={'row'}>
                    <div className={`w-100 ${!this.props.fullWidth && 'container'}`}>
                        <ul className="keywords__list list-unstyled row">
                            {this.KeywordList()}
                        </ul>
                    </div>
                </div>
            </div>
        </>)
    }
}

export default KeywordsPage;
