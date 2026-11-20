import {useAsync} from "./translated";
import {loadAmdModule} from "./amd";

type Props = {
    name: string,
    component?: string,
    title?: string,
    className?: string
}

export default function Pix({name, component, title, className}: Props) {
    const html = useAsync(null, () => loadAmdModule('core/templates')
        .then(({renderPix}) => renderPix(name, component ?? "core", title)));

    return html ? <span className={className} dangerouslySetInnerHTML={{__html: html}}/> : null;
}