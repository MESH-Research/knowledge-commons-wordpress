import React, { useRef }  from "react";
import {HashLink as Link} from "react-router-hash-link";
import uuid from "uuid/v4";
const scrollWithOffset = (el, offset) => {
    const elementPosition = el.offsetTop - offset;
    window.scroll({
        top: elementPosition,
        left: 0,
        behavior: "smooth"
    });
};
// reusable sidebar component. You feed it data object and it builds the list html
export default class Sidebar extends React.Component {
    constructor(props) {
        super(props);
        this.myRef = React.createRef();
        this.bemRoot = this.props.bemRoot ? this.props.bemRoot + "__sidebar__" : "sidebar__";
    }

    render() {
        return (
            <div className={"section " + this.bemRoot + "section"} >
                {this.props.list.title && (
                    <h3 className={"title " + this.bemRoot + "title"}>{this.props.list.title}</h3>)}
                <ul className={"ul " + this.bemRoot + "ul list-unstyled"}>
                    {
                      this.props.list.items.map(item => (
                            <li className={this.bemRoot + "li"} key={uuid()}>


                              {


                                item.name && item.fullUrl ?
                                  <Link to={item.fullUrl}>
                                      <div dangerouslySetInnerHTML={{__html: item.name}}/>
                                  </Link>
                                : (
                                  item.url && item.name ?
                                  <Link scroll={el => scrollWithOffset(el, 50)} to={
                                      (
                                          (
                                              item.url.indexOf("http://") === 0 ||
                                              item.url.indexOf("https://") === 0
                                          ) ||
                                          item.url.indexOf("/") === 0
                                      )
                                          ? item.url
                                          : "#" + item.url.replace(/[.,\/#!$%\^&\*;:{}=_'’`“”~()\?]/g, "")
                                  }>
                                      {/*dangerouslySetInnerHTML required to parse HTML encoded titles*/}
                                      <div dangerouslySetInnerHTML={{__html: item.name}}/>
                                  </Link>
                                  : <div dangerouslySetInnerHTML={{__html: item.name}}/>

                                )

                              }
                            </li>
                        ))
                    }
                </ul>
            </div>
        );

    }
}
