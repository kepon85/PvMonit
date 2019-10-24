
import yaml

with open('config-domo.yaml') as f:
    
    data = yaml.load(f, Loader=yaml.FullLoader)
    print(data)
    print(data['books']['given'])
