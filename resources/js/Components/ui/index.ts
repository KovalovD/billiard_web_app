// resources/js/Components/ui/index.ts
import Button from './Button.vue';
import Input from './Input.vue';
import Label from './Label.vue';
import Card from './Card.vue';
import CardHeader from './CardHeader.vue';
import CardTitle from './CardTitle.vue';
import CardDescription from './CardDescription.vue';
import CardContent from './CardContent.vue';
import CardFooter from './CardFooter.vue';
import Modal from './Modal.vue';
import Select from './Select.vue';
import SelectContent from './SelectContent.vue';
import SelectItem from './SelectItem.vue';
import SelectTrigger from './SelectTrigger.vue';
import SelectValue from './SelectValue.vue';
import Textarea from './Textarea.vue';
import Spinner from './Spinner.vue';

export {default as DataTable} from './data-table/DataTable.vue';

// Export both as default and named exports to support both import styles
export {
    Button,
    Input,
    Label,
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent,
    CardFooter,
    Modal,
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
    Textarea,
    Spinner
};

// Also provide default export for flexibility
export default {
    Button,
    Input,
    Label,
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent,
    CardFooter,
    Modal,
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
    Textarea,
    Spinner
};
