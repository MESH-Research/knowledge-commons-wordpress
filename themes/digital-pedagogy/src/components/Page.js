import React from "react";
import Sidebar from "./Sidebar";
import PageSection from "./PageSection";
import uuid from "uuid/v4";
import {q_pageBySlug, q_pageSingleBySlug} from "../graphql/bin/queries";
import {ScaleLoader} from "react-spinners";
import NotFound from "../components/NotFound";
import {HashLink as Link} from "react-router-hash-link";

// This component will be executed prior to any 404 page to search is there is a match.
// As such any page can easily be created via the page section of the wordpress admin.
class Page extends React.Component {
    state;

    constructor(props) {
        super(props);
        const uri = this.props.match.params.parent ? this.props.match.params.parent + "/" + this.props.match.params.id : this.props.match.params.id;
        this.state = {
            data: this.getData(uri),
            data_ready: false,
            is_sidebar: false,
            sidebar_data: false,
            prev: false,
            next: false,
            is404: false
        };
        this.getData = this.getData.bind(this);
        this.bodyHTML = this.bodyHTML.bind(this);
        this.secondaryHTML = this.secondaryHTML.bind(this);
        this.sidebarHTML = this.sidebarHTML.bind(this);
        this.get_navLinks = this.get_navLinks.bind(this);
    }

    getData(slug) {
        return q_pageBySlug(slug.toLowerCase()).then(results => {
            const data = results.data;

            // a page is not found with that url slug we forward the page the 404 NotFound page.
            if (!data.pageBy) {
                this.setState({is404: true});
                return null;
            }
            //console.log(data.pageBy);
            const result = {
                main: data.pageBy,
                secondary: data.pageBy.childPages.nodes.filter((obj) => obj.title.toLowerCase() !== "sidebar"),
                sidebar: data.pageBy.childPages.nodes.filter((obj) => obj.title.toLowerCase() === "sidebar")
            };
            this.setState({data: result, data_ready: true});
            this.checkIfThereIsSideBarContent(result);
            return result;
        }).catch(e => {
            console.log("Promise Rejected Page::get_data()");
            console.log(JSON.stringify(e));
            this.setState({is404: true});
        });
    }

    async checkIfThereIsSideBarContent(result) {
        //result.sidebar.length > 0 && !!result.sidebar[0].childPages.nodes.length;
        //check if there is a sidebar post
        if (result.sidebar.length > 0 && !!result.sidebar[0].childPages.nodes.length) {
            this.setState({is_sidebar: true, data_ready: true});

            return true;
        }

        //If the main article has children we always want an index sidebar.
        //check if there are children post
        if (result.secondary.length > 0) {
            const data = await this.makeListOutOfChildrenPost(result.secondary, result.main);
            this.setState({is_sidebar: true, data_ready: true, sidebar_data: data});
            this.get_navLinks(this.state.sidebar_data);
            return true;
        } else if (!!this.props.match.params.parent) {
            this.setState({data_ready: false});
            //No children post. Check if the parent is provided and if it is we pull the parent record.
            return await this.getParentsChildrenPages(this.props.match.params.parent).then(async (results) => {
                const data = await this.makeListOutOfChildrenPost(results.childPages.nodes.filter((obj) => obj.title.toLowerCase() !== "sidebar"), results);
                this.setState({is_sidebar: true, data_ready: true, sidebar_data: data});
                this.get_navLinks(this.state.sidebar_data);
                return true;
            });
        }
        return false;
    }

    get_navLinks(data = {}) {
        const page = this.props.location.pathname.replace(/\/$/, "");
        let current = '';
        let next = '';
        let prev = '';
        if (!data) {
            console.log('No sidebar data');
        } else {
            const items = data.items;
            //console.log('get_navLinks running on ' + page, items);
            const length = data.items.length;
            let c_page = false;
            let c_index = false;
            items.map((doc, index) => {
                //console.log('inside map', doc);
                c_page = doc.url;
                //console.log('c_page:', c_page);
                if (page === c_page) {
                    //console.log('start ================> ');
                    //console.log('looping page', c_page);
                    //console.log('location page', page);
                    //console.log('found current page', c_page);
                    current = true;
                    c_index = index;
                    //console.log('current index', c_index);
                    //console.log('prevIndex', c_index - 1);
                    if (c_index - 1 >= 0) {
                        const docs = items[c_index - 1];
                        prev = {url: docs.url, name: docs.name};
                    }
                    //console.log('nextIndex', c_index + 1);
                    if (c_index + 1 < length) {
                        const docs = items[(c_index + 1)];
                        next = {url: docs.url, name: docs.name};
                    }
                    //console.log('end ==================> ');
                }
            });
        }
        this.setState({prev, next});

        return {prev, next}
    }

    getParentsChildrenPages(slug = this.props.match.params.parent) {

        return q_pageSingleBySlug(slug.toLowerCase()).then(results => {
            return results.data.pageBy;
        }).catch(e => {
            console.log("Promise Rejected q_pageSingleBySlug()");
            console.log(JSON.stringify(e));
            this.setState({is404: true});
        });
    }

    makeListOutOfChildrenPost(children, parent) {
        //console.log(parent.slug);
        let items = [{
            name: parent.title,
            url: "/" + parent.slug
        }];
        children.forEach(function (child, index) {
                items[index + 1] = {
                    name: child.title,
                    url: "/" + parent.slug + "/" + child.slug,
                }
            }
        );
        return {title: "Index", items};
    }

    componentDidCatch(error, info) {
        console.log(error);
        console.log(info);
        //if this component caught an error we goto 404 page.
        this.setState({is404: true});
    }

// primary html is the parent page that matches the slug.
    primaryHTML() {
        const main = this.state.data.main;
        document.title = "Digital Pedagogy | " + main.title;
        return (
            <div className={`page__body__sections container`}>
                <PageSection key={uuid()}
                             html={`<div className='page-content ${main.slug}'><h1>${main.title}</h1>${main.content}</div>`}
                             bemRoot={`page__body`}
                             sectionName={main.slug}/>
            </div>
        )
    }

// secondary html are child pages tied to the primary page.
    secondaryHTML() {
        const sec = this.sortObjByMenuOrder(this.state.data.secondary);
        return sec.map((main) => {
            return (
                <PageSection key={uuid()} html={`<h2>${main.title}</h2>${main.content}`} bemRoot={`page__body`}
                             sectionName={main.slug}/>
            )
        });
    }

// wrapper method to pull in primary and secondary html.
    bodyHTML() {
        return (
            <>
                {this.primaryHTML()}
                {this.state.data.main.commentStatus === "closed" && this.secondaryHTML()}
            </>
        )
    }

// this sorts the page order of the primary and secondary pages via the menuOrder entries in the wp post table.
    sortObjByMenuOrder(obj) {
        return obj.sort((a, b) => {
            const nameA = a.menuOrder;
            const nameB = b.menuOrder;
            let comparison = 0;
            if (nameA > nameB) {
                comparison = 1;
            } else if (nameA < nameB) {
                comparison = -1;
            }
            return comparison;
        })
    }

// data formatting and pushed to the sidebar component
    sidebarBuildData(data = {}) {
        if (data === false) {
            const sidebar = this.state.data.sidebar[0];
            const children = this.sortObjByMenuOrder(sidebar.childPages.nodes);
            children.map((sb) => {
                const dom = new DOMParser();
                const ul = dom.parseFromString(sb.content, 'text/html');
                const li = ul.getElementsByTagName('ul')[0].children;
                data = {title: sb.title};
                let items = [];
                for (let i = 0; i < li.length; i++) {
                    items[i] = {name: li[i].innerHTML};
                }
                data = {...data, items: items};
            });
            this.setState({sidebar_data: data});
        }
        return data;
    }

    sidebarHTML(data = {}) {
        data = this.sidebarBuildData(data);
        if (data !== false) {
            return <Sidebar key={uuid()} bemRoot={"page"} list={data}/>
        }
    }

    scrollToNoteLink() {
        const noteLinks = document.querySelectorAll('.ftnref, .ftn')
        noteLinks.forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                let target = el.getAttribute('href');
                //console.log(target);
                target = document.querySelector("a[href='" + target + "']");
                //console.log(target);
                let note = document.querySelector("a[href='#" + target.className + target.innerHTML + "']");
                //console.log(note);
                note.scrollIntoView(true);
                window.scrollBy(0, -150);
            })
        })
    }

    componentDidMount() {
        window.scrollTo(0, 0);
        this.scrollToNoteLink();
    }

    componentDidUpdate() {
        window.scrollTo(0, 0);
        this.scrollToNoteLink();
    }


//HTML rendered to page
    render() {
        if (!this.state.data_ready && !this.state.is404) {
            return (<div>
                <div className="w-100 p-5 middle-center" role="main" id="main-content">
                    <ScaleLoader
                        sizeUnit={"px"}
                        size={75}
                        color={'#7eafbc'}
                        loading={true}/>
                </div>
            </div>);
        }
        if (this.state.is404) {
            return <NotFound/>
        }
        // console.log('rendering page');
        // console.log('prev nav links', this.state.prev);
        // console.log('next nav links', this.state.next);
        const bodyColumns = this.state.is_sidebar ? " col-md-9 col-lg-10 " : ' col-md-12 ';
        //const navs = this.state.sidebar_data && this.get_navLinks(this.state.sidebar_data);
        return (
            <div>
                <div className='row'>
                    <div className={`page__body ${bodyColumns}`}>
                        <div className='page__body__container '>
                            <div className='row'>
                                <div className='page__body__sections container'>
                                    <main id="main-content">
                                        {this.bodyHTML()}
                                    </main>
                                </div>
                            </div>
                            <div className='row'>
                                <div className='col-6'>{this.state.prev &&
                                <Link className='previous-page' to={this.state.prev.url}>{<div
                                    dangerouslySetInnerHTML={{__html: this.state.prev.name}}/>}</Link>}</div>
                                <div className='col-6 text-right'>{this.state.next &&
                                <Link className='next-page' to={this.state.next.url}>
                                    <div
                                        dangerouslySetInnerHTML={{__html: this.state.next.name}}/>
                                </Link>}</div>
                            </div>
                        </div>
                    </div>
                    {this.state.is_sidebar && (<div className='page__sidebar col-md-3 col-lg-2 order-md-first '>
                        {this.sidebarHTML(this.state.sidebar_data)}
                    </div>)}
                </div>
            </div>
        );
    }
}

export default Page;
// export default connect()(Page);
